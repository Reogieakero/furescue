<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__) . '/views/path.php';
require_once \shared_path('date-range-picker/date-range-picker.php');

class DateRangePickerTest extends TestCase
{
    public function testEmitsHiddenStartAndEndAndLabel(): void
    {
        $html = \date_range_picker([
            'id' => 'analytics-range',
            'start_id' => 'range-start',
            'end_id' => 'range-end',
            'start' => '2026-04-01',
            'end' => '2026-04-30',
            'max' => '2026-09-06',
            'placeholder' => 'All dates',
            'className' => 'analytics-range-picker',
        ]);

        $this->assertStringContainsString('id="analytics-range"', $html);
        $this->assertStringContainsString('data-date-range', $html);
        $this->assertStringContainsString('id="range-start"', $html);
        $this->assertStringContainsString('id="range-end"', $html);
        $this->assertStringContainsString('value="2026-04-01"', $html);
        $this->assertStringContainsString('value="2026-04-30"', $html);
        $this->assertStringContainsString('Apr 1, 2026 – Apr 30, 2026', $html);
        $this->assertStringContainsString('analytics-range-picker', $html);
        $this->assertStringContainsString('data-max="2026-09-06"', $html);
        $this->assertStringContainsString('data-range-today', $html);
        $this->assertStringContainsString('data-range-clear', $html);
        $this->assertStringContainsString('data-range-apply', $html);
        $this->assertStringContainsString('The start date must be on or before the end date.', $html);
        $this->assertStringNotContainsString('data-range-preset', $html);
    }

    public function testEmptyRangeUsesPlaceholder(): void
    {
        $html = \date_range_picker([
            'id' => 'gis-date-range',
            'placeholder' => 'Any dates',
        ]);

        $this->assertStringContainsString('is-placeholder', $html);
        $this->assertStringContainsString('Any dates', $html);
        $this->assertStringContainsString('id="gis-date-range-start"', $html);
        $this->assertStringContainsString('id="gis-date-range-end"', $html);
    }

    public function testStartOnlyUsesFromLabel(): void
    {
        $html = \date_range_picker([
            'start' => '2026-09-06',
        ]);

        $this->assertStringContainsString('From Sep 6, 2026', $html);
    }

    public function testEscapesIdsAndValues(): void
    {
        $html = \date_range_picker([
            'id' => 'id"x',
            'start' => '2026-01-01',
            'end' => '2026-01-02',
        ]);

        $this->assertStringContainsString('id="id&quot;x"', $html);
    }

    public function testFactoryFileDefinesFunctionWithoutOutput(): void
    {
        ob_start();
        require_once \shared_path('date-range-picker/date-range-picker.php');
        $out = ob_get_clean();

        $this->assertSame('', $out);
        $this->assertTrue(function_exists('\\date_range_picker'));
    }
}
