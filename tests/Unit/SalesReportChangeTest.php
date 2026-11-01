<?php

namespace Tests\Unit;

use App\Support\SalesReport;
use PHPUnit\Framework\TestCase;

/** Pure maths, no database or framework needed. */
class SalesReportChangeTest extends TestCase
{
    public function test_no_previous_figure_means_no_comparison(): void
    {
        $this->assertNull(SalesReport::change(500, 0));
    }

    public function test_growth_and_decline_are_signed(): void
    {
        $this->assertSame('+50%', SalesReport::change(150, 100));
        $this->assertSame('-25%', SalesReport::change(75, 100));
        $this->assertSame('+0%', SalesReport::change(100, 100));
    }
}
