<?php

namespace App\Support;

use Illuminate\Http\Request;

class IngestToken
{
    public static function fromRequest(Request $request): string
    {
        $token = $request->input('token')
            ?? $request->query('token')
            ?? $request->header('X-Site-Token');

        return trim((string) ($token ?? ''));
    }
}
