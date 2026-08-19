<?php

namespace App\Tests;

use App\Services\DedupService;
use PHPUnit\Framework\TestCase;

class DedupServiceTest extends TestCase
{
    public function testContentHashIsDeterministic(): void
    {
        $a = DedupService::contentHash('  Injured  dog  ', 6.95, 126.22, '2026-08-19');
        $b = DedupService::contentHash('Injured dog', 6.95, 126.22, '2026-08-19');
        $this->assertSame($a, $b, 'Normalized identical descriptions must hash equally');
    }

    public function testContentHashDiffersByLocation(): void
    {
        $a = DedupService::contentHash('Injured dog', 6.950, 126.220, '2026-08-19');
        $b = DedupService::contentHash('Injured dog', 6.951, 126.221, '2026-08-19');
        $this->assertNotSame($a, $b, 'Different rounded locations must hash differently');
    }

    public function testContentHashDiffersByDay(): void
    {
        $a = DedupService::contentHash('Injured dog', 6.95, 126.22, '2026-08-19');
        $b = DedupService::contentHash('Injured dog', 6.95, 126.22, '2026-08-20');
        $this->assertNotSame($a, $b);
    }
}
