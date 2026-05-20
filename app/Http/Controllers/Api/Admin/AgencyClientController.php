<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AgencyClientResource;
use App\Http\Resources\Admin\CabinetUserResource;
use App\Http\Resources\Admin\SiteResource;
use App\Models\AgencyClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AgencyClientController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $clients = AgencyClient::query()
            ->withCount('sites')
            ->orderBy('name')
            ->paginate(20);

        return AgencyClientResource::collection($clients);
    }

    public function show(AgencyClient $client): JsonResponse
    {
        $client->loadCount(['sites', 'leads']);
        $client->load([
            'sites' => fn ($query) => $query->withCount('leads')->orderBy('name'),
        ]);

        $users = $client->users()
            ->where('role', UserRole::ClientUser)
            ->with('sites:id,name')
            ->orderBy('email')
            ->get();

        return response()->json([
            'data' => new AgencyClientResource($client),
            'sites' => SiteResource::collection($client->sites),
            'users' => CabinetUserResource::collection($users),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $client = AgencyClient::query()->create($this->validated($request));

        return (new AgencyClientResource($client))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, AgencyClient $client): AgencyClientResource
    {
        $client->update($this->validated($request));

        return new AgencyClientResource($client->fresh());
    }

    public function destroy(AgencyClient $client): JsonResponse
    {
        $client->delete();

        return response()->json(['message' => 'deleted']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'inn' => ['nullable', 'string', 'max:12'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:32'],
            'manager_comment' => ['nullable', 'string'],
            'status' => ['required', 'in:active,archived'],
        ]);
    }
}
