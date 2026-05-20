<?php

namespace App\Http\Controllers\Api\Client;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\Client\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => ['Неверный email или пароль.'],
            ]);
        }

        $user = Auth::user();

        if ($user->role !== UserRole::ClientUser || $user->agency_client_id === null || ! $user->is_active) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => ['Доступ только для пользователей личного кабинета.'],
            ]);
        }

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        $user->load('agencyClient');

        return response()->json([
            'user' => (new UserResource($user))->resolve(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json(['message' => 'ok']);
    }

    public function user(Request $request): UserResource
    {
        $request->user()->load('agencyClient');

        return new UserResource($request->user());
    }
}
