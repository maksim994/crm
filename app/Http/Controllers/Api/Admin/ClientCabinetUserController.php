<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\CabinetUserResource;
use App\Models\AgencyClient;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ClientCabinetUserController extends Controller
{
    public function index(AgencyClient $client): AnonymousResourceCollection
    {
        $users = $client->users()
            ->where('role', UserRole::ClientUser)
            ->with('sites:id,name')
            ->orderBy('name')
            ->get();

        return CabinetUserResource::collection($users);
    }

    public function show(AgencyClient $client, User $cabinetUser): CabinetUserResource
    {
        $this->ensureCabinetUser($client, $cabinetUser);
        $cabinetUser->load('sites:id,name');

        return new CabinetUserResource($cabinetUser);
    }

    public function store(Request $request, AgencyClient $client): JsonResponse
    {
        $data = $this->validated($request, $client);
        $siteIds = $data['site_ids'] ?? [];

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => UserRole::ClientUser,
            'agency_client_id' => $client->id,
            'cabinet_all_sites' => $data['cabinet_all_sites'],
            'is_active' => $data['is_active'],
        ]);

        if (! $data['cabinet_all_sites']) {
            $user->sites()->sync($siteIds);
        }

        $user->load('sites:id,name');

        return response()->json([
            'data' => new CabinetUserResource($user),
            'generated_password' => $data['password'],
        ], 201);
    }

    public function update(Request $request, AgencyClient $client, User $cabinetUser): CabinetUserResource
    {
        $this->ensureCabinetUser($client, $cabinetUser);

        $data = $this->validated($request, $client, updating: true);
        $siteIds = $data['site_ids'] ?? [];

        $cabinetUser->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'cabinet_all_sites' => $data['cabinet_all_sites'],
            'is_active' => $data['is_active'],
        ]);

        if (! empty($data['password'] ?? null)) {
            $cabinetUser->password = Hash::make($data['password']);
        }

        $cabinetUser->save();

        if ($data['cabinet_all_sites']) {
            $cabinetUser->sites()->detach();
        } else {
            $cabinetUser->sites()->sync($siteIds);
        }

        $cabinetUser->load('sites:id,name');

        return new CabinetUserResource($cabinetUser);
    }

    public function destroy(AgencyClient $client, User $cabinetUser): JsonResponse
    {
        $this->ensureCabinetUser($client, $cabinetUser);
        $cabinetUser->delete();

        return response()->json(['message' => 'deleted']);
    }

    private function ensureCabinetUser(AgencyClient $client, User $user): void
    {
        if ($user->role !== UserRole::ClientUser || $user->agency_client_id !== $client->id) {
            abort(404);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, AgencyClient $client, bool $updating = false): array
    {
        $clientSiteIds = $client->sites()->pluck('id')->all();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($request->route('cabinetUser')),
            ],
            'cabinet_all_sites' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'site_ids' => ['nullable', 'array'],
            'site_ids.*' => ['uuid', Rule::in($clientSiteIds)],
        ];

        if ($updating) {
            $rules['password'] = ['nullable', 'string', Password::defaults()];
        } else {
            $rules['password'] = ['required', 'string', Password::defaults()];
        }

        $data = $request->validate($rules);

        if (! $data['cabinet_all_sites']) {
            $request->validate([
                'site_ids' => ['required', 'array', 'min:1'],
                'site_ids.*' => ['uuid', Rule::in($clientSiteIds)],
            ]);
            $data['site_ids'] = $request->input('site_ids', []);
        } else {
            $data['site_ids'] = [];
        }

        return $data;
    }
}
