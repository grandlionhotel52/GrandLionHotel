<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_display_format_omits_only_unnecessary_decimal_zeroes(): void
    {
        $this->assertSame('5,000', Money::display(5000));
        $this->assertSame('5,000.50', Money::display(5000.50));
        $this->assertSame('5,000.25', Money::display(5000.25));
    }

    public function test_exact_format_keeps_two_decimal_places_for_financial_records(): void
    {
        $this->assertSame('5,000.00', Money::exact(5000));
        $this->assertSame('5,000.50', Money::exact(5000.50));
    }
}
