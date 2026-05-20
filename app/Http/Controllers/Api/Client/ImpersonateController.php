<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\Client\UserResource;
use App\Support\CabinetImpersonation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ImpersonateController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'size:48'],
        ]);

        $user = CabinetImpersonation::consume($data['token']);

        if ($user === null) {
            throw ValidationException::withMessages([
                'token' => ['Ссылка для входа недействительна или устарела.'],
            ]);
        }

        Auth::login($user);

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        $user->load('agencyClient');

        return response()->json([
            'user' => (new UserResource($user))->resolve(),
        ]);
    }
}
