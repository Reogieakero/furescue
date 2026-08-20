<?php

namespace App\Auth;

use App\Database;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

class GoogleAuthService
{
    private const CERTS_URL = 'https://www.googleapis.com/oauth2/v3/certs';
    private const ISSUERS = ['accounts.google.com', 'https://accounts.google.com'];

    private string $clientId;

    public function __construct()
    {
        $this->clientId = (string) Database::env('GOOGLE_CLIENT_ID', '');
    }

    public function verifyIdToken(string $idToken): ?array
    {
        if ($this->clientId === '') {
            return null;
        }

        $kid = $this->headerKid($idToken);
        if ($kid === null) {
            return null;
        }

        $certs = $this->fetchCerts();
        if (!isset($certs[$kid])) {
            return null;
        }

        try {
            $decoded = JWT::decode($idToken, new Key($certs[$kid], 'RS256'));
        } catch (ExpiredException | SignatureInvalidException | BeforeValidException | \UnexpectedValueException $e) {
            return null;
        }

        $payload = (array) $decoded;

        if (!in_array($payload['iss'] ?? '', self::ISSUERS, true)) {
            return null;
        }
        if (($payload['aud'] ?? '') !== $this->clientId) {
            return null;
        }
        if (($payload['exp'] ?? 0) < time()) {
            return null;
        }

        return $payload;
    }

    private function headerKid(string $idToken): ?string
    {
        $parts = explode('.', $idToken);
        if (count($parts) < 2) {
            return null;
        }
        $header = json_decode($this->base64UrlDecode($parts[0]), true);
        return is_array($header) ? ($header['kid'] ?? null) : null;
    }

    private function fetchCerts(): array
    {
        $json = @file_get_contents(self::CERTS_URL);
        if ($json === false) {
            return [];
        }
        $data = json_decode($json, true);
        if (!is_array($data) || !isset($data['keys'])) {
            return [];
        }
        $map = [];
        foreach ($data['keys'] as $key) {
            if (($key['kty'] ?? '') !== 'RSA') {
                continue;
            }
            $kid = $key['kid'] ?? null;
            if ($kid === null) {
                continue;
            }
            $map[$kid] = $this->rsaPemFromJwk($key);
        }
        return $map;
    }

    private function rsaPemFromJwk(array $jwk): string
    {
        $modulus = $this->base64UrlDecode($jwk['n']);
        $exponent = $this->base64UrlDecode($jwk['e']);

        $modulus = ltrim($modulus, "\x00");
        $exponent = ltrim($exponent, "\x00");

        $modulus = $this->derEncodeInteger($modulus);
        $exponent = $this->derEncodeInteger($exponent);

        $rsaPublicKey = $this->derSequence($modulus . $exponent);

        $oid = pack('H*', '300d06092a864886f70d0101010500');
        $publicKey = $this->derSequence($oid . $this->derEncodeLength(strlen($rsaPublicKey)) . $rsaPublicKey);

        $pem = "-----BEGIN PUBLIC KEY-----\n";
        $pem .= chunk_split(base64_encode($publicKey), 64, "\n");
        $pem .= "-----END PUBLIC KEY-----\n";
        return $pem;
    }

    private function derSequence(string $data): string
    {
        return "\x30" . $this->derEncodeLength(strlen($data)) . $data;
    }

    private function derEncodeInteger(string $data): string
    {
        if (ord($data[0]) & 0x80) {
            $data = "\x00" . $data;
        }
        return "\x02" . $this->derEncodeLength(strlen($data)) . $data;
    }

    private function derEncodeLength(int $length): string
    {
        if ($length < 128) {
            return chr($length);
        }
        $bytes = '';
        while ($length > 0) {
            $bytes = chr($length & 0xff) . $bytes;
            $length >>= 8;
        }
        return chr(0x80 | strlen($bytes)) . $bytes;
    }

    private function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/'), true) ?: '';
    }
}
