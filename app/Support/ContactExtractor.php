<?php

namespace App\Support;

class ContactExtractor
{
    public static function phone(?string $text): ?string
    {
        if ($text === null || trim($text) === '') {
            return null;
        }

        if (preg_match('/(?:\+7|8)[\s\-()]*\d{3}[\s\-()]*\d{3}[\s\-()]*\d{2}[\s\-()]*\d{2}/u', $text, $matches)) {
            $digits = preg_replace('/\D+/', '', $matches[0]);

            if (str_starts_with($digits, '8') && strlen($digits) === 11) {
                $digits = '7'.substr($digits, 1);
            }

            if (strlen($digits) === 11 && str_starts_with($digits, '7')) {
                return '+'.$digits;
            }
        }

        return null;
    }

    public static function email(?string $text): ?string
    {
        if ($text === null || trim($text) === '') {
            return null;
        }

        if (preg_match('/[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}/i', $text, $matches)) {
            return strtolower($matches[0]);
        }

        return null;
    }
}
