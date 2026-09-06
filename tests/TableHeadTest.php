<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__) . '/views/path.php';
require_once \shared_path('table/table.php');

class TableHeadTest extends TestCase
{
    public function testEmitsHeadRow(): void
    {
        $html = \table_head(['Case', 'Status']);

        $this->assertStringContainsString('<tr class="table-head">', $html);
        $this->assertStringContainsString('<th>Case</th>', $html);
        $this->assertStringContainsString('<th>Status</th>', $html);
    }

    public function testEscapesColumnLabels(): void
    {
        $html = \table_head(['<b>']);

        $this->assertStringContainsString('<th>&lt;b&gt;</th>', $html);
    }

    public function testFactoryFileDefinesFunctionWithoutOutput(): void
    {
        ob_start();
        require_once \shared_path('table/table.php');
        $out = ob_get_clean();

        $this->assertSame('', $out);
        $this->assertTrue(function_exists('\\table_head'));
    }
}
