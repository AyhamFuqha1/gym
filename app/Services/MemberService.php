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
    private function syncExpiredSubscriptionStatusForUser(int $userId): void
    {
        $latestSubscription = Subscription::where('user_id', $userId)
            ->latest('id')
            ->first();

        if (!$latestSubscription) {
            User::where('id', $userId)->update([
                'status' => 'expired',
                'number_day' => 0,
            ]);
            return;
        }

        $today = Carbon::today();

       if (
            $latestSubscription->status === 'active' &&
            Carbon::parse($latestSubscription->end_date)->lte($today)
        ) {
            $latestSubscription->update([
                'status' => 'expired',
            ]);
        }

        $latestSubscription->refresh();

        $userStatus = match ($latestSubscription->status) {
            'active' => 'active',
            'frozen' => 'frozen',
            'expired' => 'expired',
            'cancelled' => 'cancelled',
            default => 'expired',
        };

        $remainingDays = 0;

        if (
            $latestSubscription->status === 'active' &&
            Carbon::parse($latestSubscription->end_date)->gt($today)
        ) {
            $remainingDays = $today->diffInDays(Carbon::parse($latestSubscription->end_date));
        }

        User::where('id', $userId)->update([
            'status' => $userStatus,
            'number_day' => $remainingDays,
        ]);
    }

    private function syncExpiredSubscriptionStatusesForMembers(): void
    {
        $userIds = Subscription::distinct()->pluck('user_id');

        foreach ($userIds as $userId) {
            $this->syncExpiredSubscriptionStatusForUser((int) $userId);
        }
    }

    public function index()
    {
        $this->syncExpiredSubscriptionStatusesForMembers();

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
        $this->syncExpiredSubscriptionStatusForUser((int) $id);

        return User::where('role_id', 4)
            ->where('id', $id)
            ->with('subscription')
            ->get();
    }

    public function reNewSubscription($data)
    {
        return DB::transaction(function () use ($data) {
            $this->syncExpiredSubscriptionStatusForUser((int) $data->user_id);

            $plan = Plan::findOrFail($data->plan_id);

            $existingActiveSubscription = Subscription::where('user_id', $data->user_id)
                ->where('status', 'active')
                ->whereDate('end_date', '>=', now()->toDateString())
                ->latest('id')
                ->first();

            if ($existingActiveSubscription) {
                throw new \Exception('This member already has an active subscription.');
            }

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

            $createdSubscription = Subscription::create($sub);

            User::where('id', $data->user_id)->update([
                'status' => 'active',
                'number_day' => $plan->duration_days,
            ]);

            return [
                'success' => true,
                'message' => 'Subscription renewed successfully',
                'subscription' => $createdSubscription,
            ];
        });
    }

    public function freezeSubscription($id)
    {
        return DB::transaction(function () use ($id) {
            $this->syncExpiredSubscriptionStatusForUser((int) $id);

            $latestSubscription = Subscription::where('user_id', $id)
                ->latest('id')
                ->first();

            if (!$latestSubscription) {
                throw new \Exception('No subscription found for this member.');
            }

            if ($latestSubscription->status === 'expired') {
                throw new \Exception('Cannot freeze an expired subscription.');
            }

            $latestSubscription->update([
                'status' => 'frozen',
            ]);

            User::where("id", $id)->update([
                "status" => "frozen"
            ]);

            return [
                'success' => true,
                'message' => 'Subscription frozen successfully',
            ];
        });
    }

    public function resumeSubscription($id)
    {
        return DB::transaction(function () use ($id) {
            $this->syncExpiredSubscriptionStatusForUser((int) $id);

            $latestSubscription = Subscription::where('user_id', $id)
                ->latest('id')
                ->first();

            if (!$latestSubscription) {
                throw new \Exception('No subscription found for this member.');
            }

            if (Carbon::parse($latestSubscription->end_date)->lte(Carbon::today())) {
                $latestSubscription->update([
                    'status' => 'expired',
                ]);

                User::where("id", $id)->update([
                    "status" => "expired",
                    "number_day" => 0,
                ]);

                throw new \Exception('Cannot resume an expired subscription. Please renew it.');
            }

            $latestSubscription->update([
                'status' => 'active',
            ]);

            $remainingDays = Carbon::today()->diffInDays(Carbon::parse($latestSubscription->end_date));

            User::where("id", $id)->update([
                "status" => "active",
                "number_day" => $remainingDays,
            ]);

            return [
                'success' => true,
                'message' => 'Subscription resumed successfully',
            ];
        });
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