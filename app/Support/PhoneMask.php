<?php

namespace App\Support;

final class PhoneMask
{
    /** "0791234567" → "079•••••67" */
    public static function mask(?string $phone): ?string
    {
        if (blank($phone)) {
            return null;
        }

        $digits = preg_replace('/\s+/', '', $phone);
        $length = mb_strlen($digits);

        if ($length <= 5) {
            return str_repeat('•', $length);
        }

        return mb_substr($digits, 0, 3).str_repeat('•', $length - 5).mb_substr($digits, -2);
    }
}
