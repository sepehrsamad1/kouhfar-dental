<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

final class IranianPhoneNumber
{
    public static function normalize(string $value): string
    {
        $digits = self::digitsOnly($value);

        if (str_starts_with($digits, '0098')) {
            $digits = '98'.substr($digits, 4);
        }

        if (str_starts_with($digits, '98')) {
            $digits = '0'.substr($digits, 2);
        }

        if (! preg_match('/^09\d{9}$/', $digits)) {
            throw new InvalidArgumentException('Invalid Iranian mobile number.');
        }

        return $digits;
    }

    public static function digitsOnly(string $value): string
    {
        $translated = strtr($value, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        return preg_replace('/\D+/', '', $translated) ?? '';
    }

    public static function mask(string $normalized): string
    {
        return substr($normalized, 0, 4).'****'.substr($normalized, -4);
    }
}
