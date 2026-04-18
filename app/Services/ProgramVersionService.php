<?php

namespace App\Services;

use App\Models\ProgramVersion;

class ProgramVersionService
{
    /**
     * Create program version
     */
    public function createProgramVersion(array $data): ProgramVersion
    {
        return ProgramVersion::create($data);
    }

    /**
     * Get program version
     */
    public function getProgramVersion(int $id): ?ProgramVersion
    {
        return ProgramVersion::with(['exercises.exercise'])->find($id);
    }

    /**
     * Get program versions by user
     */
    public function getProgramVersionsByUser(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return ProgramVersion::with(['exercises.exercise'])
            ->where('user_id', $userId)
            ->get();
    }

    /**
     * Update program version
     */
    public function updateProgramVersion(int $id, array $data): ?ProgramVersion
    {
        $programVersion = ProgramVersion::find($id);

        if (!$programVersion) {
            return null;
        }

        $programVersion->update($data);

        return $programVersion;
    }

    /**
     * Delete program version
     */
    public function deleteProgramVersion(int $id): bool
    {
        $programVersion = ProgramVersion::find($id);

        if (!$programVersion) {
            return false;
        }

        $programVersion->delete();

        return true;
    }
}