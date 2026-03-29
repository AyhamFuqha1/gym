<?php

namespace App\Services;

use App\Models\UserGoal;

class UserGoalService
{
    public function index()
    {
        return UserGoal::with('user')->get();
    }

    public function store($data)
    {
        return UserGoal::create($data);
    }

    public function show($id)
    {
        return UserGoal::with('user')->findOrFail($id);
    }

    public function update($id, $data)
    {
        $goal = UserGoal::findOrFail($id);
        $goal->update($data);
        return $goal;
    }

    public function destroy($id)
    {
        $goal = UserGoal::findOrFail($id);
        $goal->delete();
        return ['message' => 'Goal deleted successfully'];
    }
}
