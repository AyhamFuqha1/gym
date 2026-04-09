<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProgramVersionRequest;
use App\Http\Requests\UpdateProgramVersionRequest;
use App\Services\ProgramVersionService;
use Illuminate\Http\JsonResponse;

class ProgramVersionController extends Controller
{
    private ProgramVersionService $programVersionService;

    public function __construct(ProgramVersionService $programVersionService)
    {
        $this->programVersionService = $programVersionService;
    }

    public function index(): JsonResponse
    {
        $programVersions = $this->programVersionService->getProgramVersionsByUser(auth()->id());

        return response()->json([
            'success' => true,
            'data' => $programVersions
        ]);
    }

    public function store(StoreProgramVersionRequest $request): JsonResponse
    {
        $programVersion = $this->programVersionService->createProgramVersion($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Program version created successfully',
            'data' => $programVersion
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $programVersion = $this->programVersionService->getProgramVersion($id);

        if (!$programVersion) {
            return response()->json([
                'success' => false,
                'message' => 'Program version not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $programVersion
        ]);
    }

    public function update(UpdateProgramVersionRequest $request, int $id): JsonResponse
    {
        $programVersion = $this->programVersionService->updateProgramVersion($id, $request->validated());

        if (!$programVersion) {
            return response()->json([
                'success' => false,
                'message' => 'Program version not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Program version updated successfully',
            'data' => $programVersion
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->programVersionService->deleteProgramVersion($id);

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Program version not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Program version deleted successfully'
        ]);
    }
}