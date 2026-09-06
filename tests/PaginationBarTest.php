<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__) . '/views/path.php';
require_once \shared_path('pagination/pagination.php');

class PaginationBarTest extends TestCase
{
    public function testEmitsNavWithCurrentPage(): void
    {
        $html = \pagination_bar(25, 10, 2);

        $this->assertStringContainsString('aria-label="Pagination"', $html);
        $this->assertStringContainsString('aria-current="page"', $html);
        $this->assertStringContainsString('data-page="2"', $html);
        $this->assertStringContainsString('Previous', $html);
        $this->assertStringContainsString('Next', $html);
    }

    public function testFactoryFileDefinesFunctionWithoutOutput(): void
    {
        ob_start();
        require_once \shared_path('pagination/pagination.php');
        $out = ob_get_clean();

        $this->assertSame('', $out);
        $this->assertTrue(function_exists('\\pagination_bar'));
    }
}
