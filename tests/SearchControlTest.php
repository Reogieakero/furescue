<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__) . '/views/path.php';
require_once \shared_path('search/search.php');

class SearchControlTest extends TestCase
{
    public function testEmitsSharedMarkupWithAliasClass(): void
    {
        $html = \search_control('case-search', 'Search case #, barangay, animal…', 'q');

        $this->assertSame(
            '<div class="search-field report-search"><i data-lucide="search"></i><input id="case-search" type="text" placeholder="Search case #, barangay, animal…" value="q"></div>',
            $html
        );
    }

    public function testAppendsExtraClassName(): void
    {
        $html = \search_control('animal-search', 'Search…', '', 'animal-search');

        $this->assertStringContainsString('class="search-field report-search animal-search"', $html);
        $this->assertStringContainsString('id="animal-search"', $html);
    }

    public function testEscapesAttributes(): void
    {
        $html = \search_control('id"x', '<b>', '"quoted"');

        $this->assertStringContainsString('id="id&quot;x"', $html);
        $this->assertStringContainsString('placeholder="&lt;b&gt;"', $html);
        $this->assertStringContainsString('value="&quot;quoted&quot;"', $html);
    }

    public function testFactoryFileDefinesFunctionWithoutOutput(): void
    {
        ob_start();
        require_once \shared_path('search/search.php');
        $out = ob_get_clean();

        $this->assertSame('', $out);
        $this->assertTrue(function_exists('\\search_control'));
    }
}
