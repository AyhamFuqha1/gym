<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserProgramRequest;
use App\Http\Requests\UpdateUserProgramRequest;
use App\Services\UserProgramService;
use Illuminate\Http\JsonResponse;

class UserProgramController extends Controller
{
    private UserProgramService $userProgramService;

    public function __construct(UserProgramService $userProgramService)
    {
        $this->userProgramService = $userProgramService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $userPrograms = $this->userProgramService->getUserProgramsByUser(auth()->id());

        return response()->json([
            'success' => true,
            'data' => $userPrograms
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserProgramRequest $request): JsonResponse
    {
        $userProgram = $this->userProgramService->createUserProgram($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'User program created successfully',
            'data' => $userProgram
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        $userProgram = $this->userProgramService->getUserProgram($id);

        if (!$userProgram) {
            return response()->json([
                'success' => false,
                'message' => 'User program not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $userProgram
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserProgramRequest $request, int $id): JsonResponse
    {
        $userProgram = $this->userProgramService->updateUserProgram($id, $request->validated());

        if (!$userProgram) {
            return response()->json([
                'success' => false,
                'message' => 'User program not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'User program updated successfully',
            'data' => $userProgram
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->userProgramService->deleteUserProgram($id);

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'User program not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'User program deleted successfully'
        ]);
    }
}
