<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\DocumentationService;
use Illuminate\Http\JsonResponse;

class DocumentationController extends Controller
{
    public function __construct(
        private readonly DocumentationService $documentation,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'groups' => $this->documentation->index(),
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        return response()->json([
            'data' => $this->documentation->show($slug),
        ]);
    }
}
