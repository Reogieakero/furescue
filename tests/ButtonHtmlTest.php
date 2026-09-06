<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__) . '/views/path.php';
require_once \shared_path('button/button.php');

class ButtonHtmlTest extends TestCase
{
    public function testEmitsButtonWithDefaultVariant(): void
    {
        $html = \button_html('Export CSV', 'outline', icon: 'download');

        $this->assertStringContainsString('type="button"', $html);
        $this->assertStringContainsString('border border-input', $html);
        $this->assertStringContainsString('data-lucide="download"', $html);
        $this->assertStringContainsString('<span>Export CSV</span>', $html);
        $this->assertStringContainsString('disabled:pointer-events-none', $html);
    }

    public function testAnchorVariant(): void
    {
        $html = \button_anchor_html('/admin/cases/', 'Cases', 'ghost');

        $this->assertStringStartsWith('<a href="/admin/cases/"', $html);
        $this->assertStringContainsString('<span>Cases</span>', $html);
    }

    public function testEscapesTextAndIcon(): void
    {
        $html = \button_html('<b>x</b>', icon: 'alert"');

        $this->assertStringContainsString('<span>&lt;b&gt;x&lt;/b&gt;</span>', $html);
        $this->assertStringContainsString('data-lucide="alert&quot;"', $html);
    }

    public function testFactoryFileDefinesFunctionWithoutOutput(): void
    {
        ob_start();
        require_once \shared_path('button/button.php');
        $out = ob_get_clean();

        $this->assertSame('', $out);
        $this->assertTrue(function_exists('\\button_html'));
        $this->assertTrue(defined('BTN_BASE'));
    }
}
