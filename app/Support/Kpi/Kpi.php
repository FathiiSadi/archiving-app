<?php

namespace App\Support\Kpi;

final class Kpi
{
    /** Percentage rounded to one decimal, or null when the denominator is empty. */
    public static function rate(float|int|null $part, float|int|null $whole): ?float
    {
        if (! $whole) {
            return null;
        }

        return round(((float) $part / (float) $whole) * 100, 1);
    }

    public static function avg(mixed $value, int $precision = 2): ?float
    {
        return $value === null ? null : round((float) $value, $precision);
    }

    public static function percent(?float $value): string
    {
        return $value === null ? '—' : self::number($value, 1).'٪';
    }

    public static function money(float|int|string|null $value): string
    {
        return self::number((float) $value, 2).' د.أ';
    }

    public static function number(float|int|string|null $value, int $decimals = 0): string
    {
        if ($value === null) {
            return '—';
        }

        $formatted = number_format((float) $value, $decimals);

        // Trim trailing zeros from the decimal part ("12.50" → "12.5", "12.00" → "12").
        if ($decimals > 0) {
            $formatted = rtrim(rtrim($formatted, '0'), '.');
        }

        return $formatted;
    }

    public static function score(?float $value, int $outOf): string
    {
        return $value === null ? '—' : self::ratio(self::number($value, 2), $outOf);
    }

    /** "a / b" kept left-to-right inside Arabic text (otherwise it renders as "b / a"). */
    public static function ratio(string|int|float $a, string|int|float $b): string
    {
        return self::ltr("{$a} / {$b}");
    }

    /** Wraps text in a Unicode left-to-right isolate. */
    public static function ltr(string $text): string
    {
        return "\u{2066}{$text}\u{2069}";
    }
}
