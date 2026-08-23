<?php

namespace App\Tests;

require_once __DIR__ . '/Support/SqliteTestDatabase.php';
require_once __DIR__ . '/Support/TestDedupService.php';
require_once __DIR__ . '/Support/InteractsWithHttp.php';

use App\Auth\GoogleAuthService;
use App\Auth\JwtService;
use App\Auth\PasswordService;
use App\Http\RouteLoader;
use App\Http\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Services\GeoService;
use App\Tests\Support\InteractsWithHttp;
use App\Tests\Support\SqliteTestDatabase;
use App\Tests\Support\TestDedupService;
use PDO;
use PHPUnit\Framework\TestCase;

class ApiIntegrationTest extends TestCase
{
    use InteractsWithHttp;

    private const MATI_LAT = 6.9554;
    private const MATI_LNG = 126.2131;

    private PDO $pdo;
    private Router $router;

    protected function setUp(): void
    {
        if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('pdo_sqlite driver is not available.');
        }

        SqliteTestDatabase::env();
        $_ENV['JWT_SECRET'] = 'integration_test_secret';
        $_ENV['JWT_REFRESH_SECRET'] = 'integration_test_refresh_secret';
        $_ENV['JWT_ALGO'] = 'HS256';
        $_ENV['JWT_TTL_MINUTES'] = '60';
        $_ENV['JWT_REFRESH_TTL_DAYS'] = '7';
        $_ENV['GOOGLE_CLIENT_ID'] = 'integration-test-client-id';

        $this->pdo = SqliteTestDatabase::create();
        $jwt = new JwtService();

        $this->router = new Router();
        RouteLoader::register($this->router, [
            'pdo' => $this->pdo,
            'jwt' => $jwt,
            'password' => new PasswordService(),
            'google' => new GoogleAuthService(),
            'dedup' => new TestDedupService($this->pdo),
            'geo' => new GeoService(),
            'authMw' => new AuthMiddleware($this->pdo, $jwt),
            'adminMw' => new RoleMiddleware(['admin']),
            'staffMw' => new RoleMiddleware(['rescuer', 'admin']),
        ]);
    }

    public function testRegisterCreatesActiveResidentAndIssuesTokens(): void
    {
        $response = $this->register();

        $this->assertTrue($response['body']['success']);
        $this->assertSame('resident', $response['body']['data']['user']['role']);
        $this->assertSame('active', $response['body']['data']['user']['account_status']);
        $this->assertNotEmpty($response['body']['data']['tokens']['access_token']);
        $this->assertNotEmpty($response['body']['data']['tokens']['refresh_token']);
        $this->assertArrayNotHasKey('password_hash', $response['body']['data']['user']);
    }

    public function testLoginReturnsTokensAndRejectsWrongPassword(): void
    {
        $this->register();

        $ok = $this->login('Password123!');
        $bad = $this->login('wrong-password');

        $this->assertTrue($ok['body']['success']);
        $this->assertNotEmpty($ok['body']['data']['tokens']['access_token']);
        $this->assertFalse($bad['body']['success']);
        $this->assertSame('INVALID_CREDENTIALS', $bad['body']['error']['code']);
    }

    public function testRefreshExchangesRefreshTokenForNewAccessToken(): void
    {
        $tokens = $this->register()['body']['data']['tokens'];

        $response = $this->call('POST', '/api/v1/auth/refresh', [
            'refresh_token' => $tokens['refresh_token'],
        ]);

        $this->assertTrue($response['body']['success']);
        $this->assertNotEmpty($response['body']['data']['access_token']);

        $forged = $this->call('POST', '/api/v1/auth/refresh', [
            'refresh_token' => $tokens['refresh_token'] . 'tamper',
        ]);
        $this->assertSame('INVALID_REFRESH_TOKEN', $forged['body']['error']['code']);
    }

    public function testCreateReportRequiresAuthThenValidatesBounds(): void
    {
        $unauthenticated = $this->call('POST', '/api/v1/reports', $this->reportBody());
        $this->assertFalse($unauthenticated['body']['success']);
        $this->assertSame('UNAUTHENTICATED', $unauthenticated['body']['error']['code']);

        $token = $this->register()['body']['data']['tokens']['access_token'];

        $outOfBounds = $this->call('POST', '/api/v1/reports', $this->reportBody(14.5995, 120.9842), $token);
        $this->assertSame('OUT_OF_BOUNDS', $outOfBounds['body']['error']['code']);

        $valid = $this->call('POST', '/api/v1/reports', $this->reportBody(), $token);
        $this->assertTrue($valid['body']['success']);
        $this->assertSame('validated', $valid['body']['data']['report']['validation_status']);
    }

    public function testListAnimalsReturnsPaginatedEnvelope(): void
    {
        $this->seedAnimal('animal-1');
        $this->seedAnimal('animal-2');

        $token = $this->register()['body']['data']['tokens']['access_token'];
        $response = $this->call('GET', '/api/v1/animals', [], $token);

        $this->assertTrue($response['body']['success']);
        $this->assertIsArray($response['body']['data']);
        $this->assertCount(2, $response['body']['data']);
        $this->assertArrayHasKey('meta', $response['body']);
        $this->assertSame(2, $response['body']['meta']['total']);
        $this->assertSame(1, $response['body']['meta']['page']);
        $this->assertSame(20, $response['body']['meta']['per_page']);
    }

    public function testAdoptionApplyOnlyAllowsAvailableAnimals(): void
    {
        $this->seedAnimal('animal-available', 'available');
        $this->seedAnimal('animal-listed', 'not_listed');
        $token = $this->register()['body']['data']['tokens']['access_token'];

        $ok = $this->call('POST', '/api/v1/adoptions', ['animal_id' => 'animal-available'], $token);
        $notAdoptable = $this->call('POST', '/api/v1/adoptions', ['animal_id' => 'animal-listed'], $token);
        $missingAnimal = $this->call('POST', '/api/v1/adoptions', ['animal_id' => 'nope'], $token);

        $this->assertTrue($ok['body']['success']);
        $this->assertSame('pending', $ok['body']['data']['adoption']['status']);
        $this->assertSame('NOT_ADOPTABLE', $notAdoptable['body']['error']['code']);
        $this->assertSame('NOT_FOUND', $missingAnimal['body']['error']['code']);
    }

    public function testUnknownRouteReturnsNotFoundEnvelope(): void
    {
        $response = $this->call('GET', '/api/v1/definitely/not/here');

        $this->assertFalse($response['body']['success']);
        $this->assertSame('NOT_FOUND', $response['body']['error']['code']);
    }

    private function call(string $method, string $path, array $body = [], ?string $accessToken = null): array
    {
        $headers = $accessToken !== null ? ['Authorization' => 'Bearer ' . $accessToken] : [];
        $request = $this->makeRequest($method, $path, $body, [], $headers);

        return $this->observe(fn () => $this->router->dispatch($request));
    }

    private function register(): array
    {
        return $this->call('POST', '/api/v1/auth/register', [
            'full_name' => 'Juan dela Cruz',
            'email' => 'juan@test.local',
            'password' => 'Password123!',
        ]);
    }

    private function login(string $password): array
    {
        return $this->call('POST', '/api/v1/auth/login', [
            'email' => 'juan@test.local',
            'password' => $password,
        ]);
    }

    private function reportBody(float $lat = self::MATI_LAT, float $lng = self::MATI_LNG): array
    {
        return [
            'animal_description' => 'Injured dog near boulevard',
            'latitude' => (string) $lat,
            'longitude' => (string) $lng,
        ];
    }

    private function seedAnimal(string $id, string $adoptionStatus = 'available'): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO animals (id, name, species, breed_type, sex, adoption_status, source)
             VALUES (?, 'Brownie', 'dog', 'aspin', 'male', ?, 'rescued_case')"
        );
        $stmt->execute([$id, $adoptionStatus]);
    }
}
