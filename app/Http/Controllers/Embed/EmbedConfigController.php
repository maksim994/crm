<?php

namespace App\Http\Controllers\Embed;

use App\Http\Controllers\Controller;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmbedConfigController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $token = trim((string) $request->query('token', ''));

        if ($token === '') {
            return $this->cors(response()->json(['message' => 'Token is required.'], 422));
        }

        $site = Site::findByToken($token);

        if ($site === null) {
            return $this->cors(response()->json(['message' => 'Unauthorized'], 401));
        }

        return $this->cors(response()->json([
            'site_id' => $site->id,
            'emails' => [
                'ads' => $site->email_inbound_address,
                'seo' => $site->email_inbound_seo,
                'other' => $site->email_inbound_other,
            ],
        ]));
    }

    private function cors(JsonResponse $response): JsonResponse
    {
        return $response
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->header('Access-Control-Max-Age', '86400');
    }
}
