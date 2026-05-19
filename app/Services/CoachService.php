<?php

namespace App\Services;

use App\Models\User;

class CoachService
{
    public function index()
    {
        return User::query()
            ->where('role_id', 3)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'status', 'created_at']);
    }
}
