<?php

namespace App\Services;

use App\Models\Profile;

class ProfileService
{
    /**
     * Create profile
     */
    public function createProfile(array $data): Profile
    {
        return Profile::create($data);
    }

    /**
     * Get profile by user_id
     */
    public function getProfileByUserId(int $userId): ?Profile
    {
        return Profile::where('user_id', $userId)->first();
    }

    /**
     * Update profile by user_id
     */
    public function updateProfileByUserId(int $userId, array $data): ?Profile
    {
        $profile = Profile::where('user_id', $userId)->first();

        if (!$profile) {
            return null;
        }

        $profile->update($data);

        return $profile;
    }

    /**
     * Delete profile by user_id
     */
    public function deleteProfileByUserId(int $userId): bool
    {
        $profile = Profile::where('user_id', $userId)->first();

        if (!$profile) {
            return false;
        }

        $profile->delete();

        return true;
    }
}