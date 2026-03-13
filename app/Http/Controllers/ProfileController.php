<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    private ProfileService $profileService;

    public function __construct(ProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    /**
     * Store new profile
     */
    public function store(ProfileRequest $request): JsonResponse
    {
        $profile = $this->profileService->createProfile($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Profile created successfully',
            'data' => $profile
        ], 201);
    }

    /**
     * Show profile
     */
    public function show(int $id): JsonResponse
    {
        $profile = $this->profileService->getProfile($id);

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Profile not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $profile
        ]);
    }

    /**
     * Update profile
     */
    public function update(UpdateProfileRequest $request, int $id): JsonResponse
    {
        $profile = $this->profileService->updateProfile($id, $request->validated());

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Profile not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $profile
        ]);
    }

    /**
     * Delete profile
     */
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->profileService->deleteProfile($id);

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Profile not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile deleted successfully'
        ]);
    }
}