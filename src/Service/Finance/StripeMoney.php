<?php

namespace App\Service\Finance;

/**
 * Conversion montant ↔ plus petite unité Stripe (centimes, millimes, etc.).
 */
final class StripeMoney
{
    /** @var list<string> */
    private const THREE_DECIMAL = ['BHD', 'JOD', 'KWD', 'OMR', 'TND'];

    /** @var list<string> */
    private const ZERO_DECIMAL = [
        'BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF',
    ];

    public static function toMinorUnits(float $amount, string $currency): int
    {
        $c = strtoupper($currency);
        if (\in_array($c, self::ZERO_DECIMAL, true)) {
            return (int) round($amount);
        }
        if (\in_array($c, self::THREE_DECIMAL, true)) {
            return (int) round($amount * 1000);
        }

        return (int) round($amount * 100);
    }

    public static function fromMinorUnits(int $minor, string $currency): float
    {
        $c = strtoupper($currency);
        if (\in_array($c, self::ZERO_DECIMAL, true)) {
            return (float) $minor;
        }
        if (\in_array($c, self::THREE_DECIMAL, true)) {
            return $minor / 1000.0;
        }

        return $minor / 100.0;
    }

    public static function formatDisplay(float $amount, string $currency): string
    {
        $c = strtoupper($currency);
        $decimals = \in_array($c, self::THREE_DECIMAL, true) ? 3 : (\in_array($c, self::ZERO_DECIMAL, true) ? 0 : 2);

        return number_format($amount, $decimals, ',', ' ').' '.$c;
    }
}
