<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgencyClient;
use App\Support\CabinetImpersonation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientCabinetImpersonateController extends Controller
{
    public function store(Request $request, AgencyClient $client): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $cabinetUser = CabinetImpersonation::resolveCabinetUser(
            $client,
            isset($data['user_id']) ? (int) $data['user_id'] : null,
        );

        $token = CabinetImpersonation::issue($client, $cabinetUser);

        return response()->json([
            'token' => $token,
            'cabinet_path' => '/cabinet/?impersonate='.$token,
            'cabinet_user' => [
                'id' => $cabinetUser->id,
                'name' => $cabinetUser->name,
                'email' => $cabinetUser->email,
            ],
        ]);
    }
}
