<?php

namespace App\Support;

class TailwindAdmin
{
    public static function asset(string $path = ''): string
    {
        $base = config('tailwind-admin.asset_path', 'tailwind-admin');

        return asset(trim($base.'/'.$path, '/'));
    }
}
