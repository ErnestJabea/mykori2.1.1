<?php

namespace Tests\Unit;

use App\Support\FinancialDecimal;
use PHPUnit\Framework\TestCase;

class FinancialDecimalTest extends TestCase
{
    public function test_fcp_parts_are_calculated_with_ten_decimal_places(): void
    {
        $this->assertSame(
            '8.1000051030',
            FinancialDecimal::partsFromAmount('1000000.00', '123456.712345')
        );
    }

    public function test_fcp_valuation_is_rounded_to_centimes(): void
    {
        $this->assertSame(
            '1000000.00',
            FinancialDecimal::fcpValuation('8.1000051030', '123456.712345')
        );
    }

    public function test_money_rounding_uses_half_up(): void
    {
        $this->assertSame('100.01', FinancialDecimal::money('100.005'));
    }
}
