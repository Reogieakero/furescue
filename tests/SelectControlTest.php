<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__) . '/views/path.php';
require_once \shared_path('select/select.php');

class SelectControlTest extends TestCase
{
    public function testEmitsTriggerAndSelectedOption(): void
    {
        $html = \select_control('report-sort', [
            ['value' => 'assigned', 'label' => 'Assigned'],
            ['value' => 'verified', 'label' => 'Verified'],
        ], 'verified', 'Sort', '', '', 'report-sort-control');

        $this->assertStringContainsString('id="report-sort"', $html);
        $this->assertStringContainsString('data-select', $html);
        $this->assertStringContainsString('<span data-select-value>Verified</span>', $html);
        $this->assertStringContainsString('report-sort-control', $html);
        $this->assertStringContainsString('aria-selected="true"', $html);
        $this->assertStringContainsString('data-value="verified"', $html);
    }

    public function testEscapesOptionValues(): void
    {
        $html = \select_control('id"x', [
            ['value' => 'a"b', 'label' => '<b>'],
        ], '', 'Pick');

        $this->assertStringContainsString('id="id&quot;x"', $html);
        $this->assertStringContainsString('data-value="a&quot;b"', $html);
        $this->assertStringContainsString('data-select-item-label>&lt;b&gt;</span>', $html);
    }

    public function testFactoryFileDefinesFunctionWithoutOutput(): void
    {
        ob_start();
        require_once \shared_path('select/select.php');
        $out = ob_get_clean();

        $this->assertSame('', $out);
        $this->assertTrue(function_exists('\\select_control'));
    }
}
