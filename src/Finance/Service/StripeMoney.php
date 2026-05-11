<?php

declare(strict_types=1);

namespace App\Finance\Service;

final class StripeMoney
{
    /** @var list<string> */
    private const THREE_DECIMAL = ['BHD', 'JOD', 'KWD', 'OMR', 'TND'];

    /** @var list<string> */
    private const ZERO_DECIMAL = [
        'BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW',
        'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF',
        'XOF', 'XPF',
    ];

    public static function toMinorUnits(float|string $amount, string $currency = 'TND'): int
    {
        $c = strtoupper(trim($currency));

        if (in_array($c, self::ZERO_DECIMAL, true)) {
            return (int) round((float) $amount);
        }
        if (in_array($c, self::THREE_DECIMAL, true)) {
            return (int) round((float) $amount * 1000);
        }

        return (int) round((float) $amount * 100);
    }

    public static function toCents(float|string $amount, string $currency = 'TND'): int
    {
        return self::toMinorUnits($amount, $currency);
    }

    public static function fromMinorUnits(int $minor, string $currency = 'TND'): float
    {
        $c = strtoupper(trim($currency));

        if (in_array($c, self::ZERO_DECIMAL, true)) {
            return (float) $minor;
        }
        if (in_array($c, self::THREE_DECIMAL, true)) {
            return $minor / 1000.0;
        }

        return $minor / 100.0;
    }

    public static function fromCents(int $cents, string $currency = 'TND'): string
    {
        return number_format(self::fromMinorUnits($cents, $currency), self::decimals($currency), '.', '');
    }

    public static function formatDisplay(float|string $amount, string $currency = 'TND'): string
    {
        $c = strtoupper(trim($currency));

        return number_format((float) $amount, self::decimals($c), ',', ' ') . ' ' . $c;
    }

    private static function decimals(string $currency): int
    {
        $c = strtoupper(trim($currency));
        if (in_array($c, self::THREE_DECIMAL, true)) {
            return 3;
        }
        if (in_array($c, self::ZERO_DECIMAL, true)) {
            return 0;
        }

        return 2;
    }
}
