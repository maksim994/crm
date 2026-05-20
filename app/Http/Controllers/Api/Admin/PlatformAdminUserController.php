<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\PlatformAdminUserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class PlatformAdminUserController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $users = User::query()
            ->where('role', UserRole::PlatformAdmin)
            ->orderBy('name')
            ->get();

        return PlatformAdminUserResource::collection($users);
    }

    public function show(User $platformAdmin): PlatformAdminUserResource
    {
        $this->ensurePlatformAdmin($platformAdmin);

        return new PlatformAdminUserResource($platformAdmin);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => UserRole::PlatformAdmin,
            'agency_client_id' => null,
            'is_active' => $data['is_active'],
        ]);

        return response()->json([
            'data' => new PlatformAdminUserResource($user),
            'generated_password' => $data['password'],
        ], 201);
    }

    public function update(Request $request, User $platformAdmin): PlatformAdminUserResource
    {
        $this->ensurePlatformAdmin($platformAdmin);

        $data = $this->validated($request, updating: true);

        if (! $data['is_active'] && $platformAdmin->is_active) {
            $this->ensureNotLastActiveAdmin($platformAdmin);
        }

        $platformAdmin->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'is_active' => $data['is_active'],
        ]);

        if (! empty($data['password'] ?? null)) {
            $platformAdmin->password = Hash::make($data['password']);
        }

        $platformAdmin->save();

        return new PlatformAdminUserResource($platformAdmin);
    }

    public function destroy(Request $request, User $platformAdmin): JsonResponse
    {
        $this->ensurePlatformAdmin($platformAdmin);

        if ($request->user()?->id === $platformAdmin->id) {
            throw ValidationException::withMessages([
                'admin' => ['Нельзя удалить собственную учётную запись.'],
            ]);
        }

        if ($platformAdmin->is_active) {
            $this->ensureNotLastActiveAdmin($platformAdmin);
        }

        $platformAdmin->delete();

        return response()->json(['message' => 'deleted']);
    }

    private function ensurePlatformAdmin(User $user): void
    {
        if ($user->role !== UserRole::PlatformAdmin) {
            abort(404);
        }
    }

    private function ensureNotLastActiveAdmin(User $user): void
    {
        $activeCount = User::query()
            ->where('role', UserRole::PlatformAdmin)
            ->where('is_active', true)
            ->count();

        if ($activeCount <= 1) {
            throw ValidationException::withMessages([
                'is_active' => ['Нельзя отключить или удалить последнего активного администратора.'],
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, bool $updating = false): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($request->route('platformAdmin')),
            ],
            'is_active' => ['required', 'boolean'],
        ];

        if ($updating) {
            $rules['password'] = ['nullable', 'string', Password::defaults()];
        } else {
            $rules['password'] = ['required', 'string', Password::defaults()];
        }

        return $request->validate($rules);
    }
}
