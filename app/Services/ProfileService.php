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
     * Get profile
     */
    public function getProfile(int $id): ?Profile
    {
        return Profile::find($id);
    }

    /**
     * Update profile
     */
    public function updateProfile(int $id, array $data): ?Profile
    {
        $profile = Profile::find($id);

        if (!$profile) {
            return null;
        }

        $profile->update($data);

        return $profile;
    }

    /**
     * Delete profile
     */
    public function deleteProfile(int $id): bool
    {
        $profile = Profile::find($id);

        if (!$profile) {
            return false;
        }

        $profile->delete();

        return true;
    }
}