<?php

namespace App\Services;

use App\Models\UserProgram;

class UserProgramService
    {


    public function createUserProgram(array $data): UserProgram
    {
        return UserProgram::create($data);
    }

    /**
     * Get user program
     */
    public function getUserProgram(int $id): ?UserProgram
    {
        return UserProgram::find($id);
    }

    /**
     * Get user programs by user id
     */
    public function getUserProgramsByUser(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return UserProgram::where('user_id', $userId)->get();
    }

    /**
     * Update user program
     */
    public function updateUserProgram(int $id, array $data): ?UserProgram
    {
        $userProgram = UserProgram::find($id);

        if (!$userProgram) {
            return null;
        }

        $userProgram->update($data);

        return $userProgram;
    }

    /**
     * Delete user program
     */
    public function deleteUserProgram(int $id): bool
    {
        $userProgram = UserProgram::find($id);

        if (!$userProgram) {
            return false;
        }

        $userProgram->delete();

        return true;
    }
}