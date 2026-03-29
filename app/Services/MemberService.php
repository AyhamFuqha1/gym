<?php

namespace App\Services;

use App\Jobs\sendRegisterEmailJob;
use App\Models\Food;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use DB;
use Hash;

class MemberService
{
    public function index()
    {
        $query = DB::table("users")
            ->where("users.role_id", 4)
            ->leftJoin("subscriptions", function ($join) {
                $join->on("users.id", "=", "subscriptions.user_id")
                    ->whereRaw('subscriptions.id IN (SELECT MAX(id) FROM subscriptions GROUP BY user_id)');
            })
            ->leftJoin("plans", "subscriptions.plan_id", "=", "plans.id");

        $members = $query->select(
            "users.id",
            "users.name as user_name",
            "plans.name as plan_name",
            "subscriptions.status",
            "subscriptions.end_date"
        )->get();

        $stats = [
            'total' => $members->count(),
            'active' => $members->where('status', 'active')->count(),
            'not_active' => $members->where('status', '!=', 'active')->count(),
        ];

        return [
            'stats' => $stats,
            'members' => $members
        ];
    }

    public function store($data, $admin)
    {
        return DB::transaction(function () use ($data, $admin) {
            $tempPassword = random_int(10000000, 99999999);

            DB::table('users')->insertGetId([
                'name' => $data->name,
                'email' => $data->email,
                'password' => Hash::make($tempPassword),
                'role_id' => $data->role_id
            ]);

            sendRegisterEmailJob::dispatch($data->email, $data->name, $tempPassword);

            return true;
        });
    }

    public function show($id)
    {
        return User::where('role_id', 4)
            ->where('id', $id)
            ->with('subscription')
            ->get();
    }

    public function reNewSubscription($data)
    {
        $plan = Plan::findOrFail($data->plan_id);

        $startDate = !empty($data->start_date)
            ? Carbon::parse($data->start_date)
            : now();

        $endDate = (clone $startDate)->addDays($plan->duration_days);

        $sub = [
            'user_id' => $data->user_id,
            'plan_id' => $data->plan_id,
            'discount' => $data->discount ?? 0,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'status' => 'active',
        ];

        Subscription::create($sub);

        User::where('id', $data->user_id)->increment('number_day', $plan->duration_days);

        return [
            'success' => true,
            'message' => 'Subscription renewed successfully',
            'subscription' => $sub,
        ];
    }

    public function freezeSubscription($id)
    {
        User::where("id", $id)->update(["status" => "frozen"]);

        return [
            'success' => true,
            'message' => 'Subscription frozen successfully',
        ];
    }

    public function resumeSubscription($id)
    {
        User::where("id", $id)->update(["status" => "active"]);

        return [
            'success' => true,
            'message' => 'Subscription resumed successfully',
        ];
    }

    public function overview($id)
    {
        $user = User::with(['Profile', 'goals'])->where('id', $id)->firstOrFail();

        return [
            'name' => $user->name,
            'email' => $user->email,
            'gender' => $user->Profile->gender ?? null,
            'age' => $user->Profile->age ?? null,
            'height' => $user->Profile->height ?? null,
            'weight' => $user->Profile->weight ?? null,
            'goal_type' => $user->goals->goal_type ?? null,
            'target_weight' => $user->goals->target_weight ?? null,
        ];
    }

    public function nutrition($id)
    {
        $user = User::with([
            'likedFoods:id,name',
            'dislikedFoods:id,name',
            'UserNutritionPlanActive.nutritionVersions.nutritions'
        ])->where('id', $id)->firstOrFail();

        $foodsAvailable = Food::whereDoesntHave('users', function ($q) use ($id) {
            $q->where('user_id', $id)
              ->where('type', 'dislike');
        })->get();

        return [
            'user' => $user,
            'available_foods' => $foodsAvailable,
        ];
    }

    public function delete()
    {
        //
    }
}