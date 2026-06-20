<?php

namespace App\Support;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

class FinancialDecimal
{
    public const MONEY_SCALE = 2;
    public const VL_SCALE = 6;
    public const PARTS_SCALE = 10;

    public static function of($value): BigDecimal
    {
        if ($value === null || $value === '') {
            return BigDecimal::zero();
        }

        return BigDecimal::of((string) $value);
    }

    public static function money($value): string
    {
        return self::of($value)->toScale(self::MONEY_SCALE, RoundingMode::HALF_UP)->__toString();
    }

    public static function vl($value): string
    {
        return self::of($value)->toScale(self::VL_SCALE, RoundingMode::HALF_UP)->__toString();
    }

    public static function parts($value): string
    {
        return self::of($value)->toScale(self::PARTS_SCALE, RoundingMode::HALF_UP)->__toString();
    }

    public static function partsFromAmount($amount, $vl): string
    {
        $vlDecimal = self::of($vl);
        if ($vlDecimal->isLessThanOrEqualTo('0')) {
            return self::parts(0);
        }

        return self::of($amount)
            ->dividedBy($vlDecimal, self::PARTS_SCALE, RoundingMode::HALF_UP)
            ->__toString();
    }

    public static function fcpValuation($parts, $vl): string
    {
        return self::of($parts)
            ->multipliedBy(self::of($vl))
            ->toScale(self::MONEY_SCALE, RoundingMode::HALF_UP)
            ->__toString();
    }

    public static function add($left, $right, int $scale = self::MONEY_SCALE): string
    {
        return self::of($left)
            ->plus(self::of($right))
            ->toScale($scale, RoundingMode::HALF_UP)
            ->__toString();
    }

    public static function subtract($left, $right, int $scale = self::MONEY_SCALE): string
    {
        return self::of($left)
            ->minus(self::of($right))
            ->toScale($scale, RoundingMode::HALF_UP)
            ->__toString();
    }

    public static function toFloat($value): float
    {
        return self::of($value)->toFloat();
    }
}
