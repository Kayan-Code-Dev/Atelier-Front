<?php

namespace App\Accounting;

final class AccountingMoney
{
    public const SCALE = 2;

    public static function of(mixed $value): string
    {
        if ($value === null || $value === '') {
            return self::zero();
        }

        if (is_string($value) && is_numeric($value)) {
            return bcadd($value, '0', self::SCALE);
        }

        if (is_int($value)) {
            return bcadd((string) $value, '0', self::SCALE);
        }

        if (is_float($value)) {
            return bcadd(number_format($value, self::SCALE, '.', ''), '0', self::SCALE);
        }

        if (is_numeric($value)) {
            return bcadd((string) $value, '0', self::SCALE);
        }

        return self::zero();
    }

    public static function zero(): string
    {
        return bcadd('0', '0', self::SCALE);
    }

    public static function add(mixed $left, mixed $right): string
    {
        return bcadd(self::of($left), self::of($right), self::SCALE);
    }

    public static function sub(mixed $left, mixed $right): string
    {
        return bcsub(self::of($left), self::of($right), self::SCALE);
    }

    public static function mul(mixed $left, mixed $right): string
    {
        return bcmul(self::of($left), self::of($right), self::SCALE);
    }

    public static function div(mixed $left, mixed $right): string
    {
        if (self::isZero($right)) {
            return self::zero();
        }

        return bcdiv(self::of($left), self::of($right), self::SCALE);
    }

    public static function cmp(mixed $left, mixed $right): int
    {
        return bccomp(self::of($left), self::of($right), self::SCALE);
    }

    public static function isZero(mixed $value): bool
    {
        return self::cmp($value, '0') === 0;
    }

    public static function isPositive(mixed $value): bool
    {
        return self::cmp($value, '0') > 0;
    }

    public static function abs(mixed $value): string
    {
        return self::cmp($value, '0') < 0 ? self::sub('0', $value) : self::of($value);
    }

    public static function toFloat(mixed $value): float
    {
        return (float) self::of($value);
    }
}
