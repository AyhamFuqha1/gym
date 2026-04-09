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

    public function store(ProfileRequest $request): JsonResponse
    {
        $profile = $this->profileService->createProfile($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Profile created successfully',
            'data' => $profile
        ], 201);
    }

    public function showByUserId(int $userId): JsonResponse
    {
        $profile = $this->profileService->getProfileByUserId($userId);

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

    public function updateByUserId(UpdateProfileRequest $request, int $userId): JsonResponse
    {
        $profile = $this->profileService->updateProfileByUserId($userId, $request->validated());

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

    public function destroyByUserId(int $userId): JsonResponse
    {
        $deleted = $this->profileService->deleteProfileByUserId($userId);

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