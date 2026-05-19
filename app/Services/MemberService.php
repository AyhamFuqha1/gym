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

        if ($latestSubscription->status === 'frozen') {
            $remainingDays = max((int) ($latestSubscription->frozen_remaining_days ?? 0), 0);
        } elseif (
            $latestSubscription->status === 'active' &&
            Carbon::parse($latestSubscription->end_date)->gt($today)
        ) {
            $remainingDays = (int) $today->diffInDays(Carbon::parse($latestSubscription->end_date)->startOfDay());
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

        $query = DB::table('users')
            ->where('users.role_id', 4)
            ->leftJoin('subscriptions', function ($join) {
                $join->on('users.id', '=', 'subscriptions.user_id')
                    ->whereRaw('subscriptions.id IN (SELECT MAX(id) FROM subscriptions GROUP BY user_id)');
            })
            ->leftJoin('plans', 'subscriptions.plan_id', '=', 'plans.id');

        $members = $query->select(
            'users.id',
            'users.name as user_name',
            'plans.name as plan_name',
            'subscriptions.status',
            'subscriptions.end_date',
            'subscriptions.frozen_at',
            'subscriptions.resumed_at',
            'subscriptions.frozen_remaining_days',
            DB::raw("
                CASE
                    WHEN subscriptions.status = 'frozen' THEN COALESCE(subscriptions.frozen_remaining_days, 0)
                    WHEN subscriptions.status = 'active' AND subscriptions.end_date > CURDATE() THEN DATEDIFF(subscriptions.end_date, CURDATE())
                    ELSE 0
                END as remaining_days
            ")
        )->get();

        $stats = [
            'total' => $members->count(),
            'active' => $members->where('status', 'active')->count(),
            'not_active' => $members->where('status', '!=', 'active')->count(),
        ];

        return [
            'stats' => $stats,
            'members' => $members,
        ];
    }

    public function store($data, $admin)
    {
        return DB::transaction(function () use ($data, $admin) {
            $tempPassword = '123456';

            DB::table('users')->insertGetId([
                'name' => $data->name,
                'email' => $data->email,
                'password' => Hash::make($tempPassword),
                'role_id' => $data->role_id,
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
            ->with('subscription.plan')
            ->get();
    }

    public function reNewSubscription($data, $createdBy = null)
    {
        return DB::transaction(function () use ($data, $createdBy) {
            $this->syncExpiredSubscriptionStatusForUser((int) $data->user_id);

            $plan = Plan::findOrFail($data->plan_id);

            $latestSubscription = Subscription::where('user_id', $data->user_id)
                ->latest('id')
                ->first();

            if ($latestSubscription?->status === 'frozen') {
                throw new \Exception('This member has a frozen subscription. Please resume or cancel it before renewing.');
            }

            if (
                $latestSubscription?->status === 'active' &&
                Carbon::parse($latestSubscription->end_date)->gt(Carbon::today())
            ) {
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

            if ($createdBy !== null) {
                $sub['created_by'] = $createdBy;
            }

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

            if ($latestSubscription->status !== 'active') {
                throw new \Exception('Only active subscriptions can be frozen.');
            }

            $today = Carbon::today();
            $endDate = Carbon::parse($latestSubscription->end_date)->startOfDay();

            if ($endDate->lte($today)) {
                $latestSubscription->update([
                    'status' => 'expired',
                ]);

                User::where('id', $id)->update([
                    'status' => 'expired',
                    'number_day' => 0,
                ]);

                throw new \Exception('Cannot freeze an expired subscription.');
            }

            $remainingDays = (int) $today->diffInDays($endDate);

            $latestSubscription->update([
                'status' => 'frozen',
                'frozen_at' => now(),
                'resumed_at' => null,
                'frozen_remaining_days' => $remainingDays,
            ]);

            User::where('id', $id)->update([
                'status' => 'frozen',
                'number_day' => $remainingDays,
            ]);

            $latestSubscription->refresh();

            return [
                'success' => true,
                'message' => 'Subscription frozen successfully',
                'remaining_days' => $remainingDays,
                'subscription' => $latestSubscription,
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

            if ($latestSubscription->status !== 'frozen') {
                throw new \Exception('Only frozen subscriptions can be resumed.');
            }

            $remainingDays = max((int) ($latestSubscription->frozen_remaining_days ?? 0), 0);

            if ($remainingDays <= 0) {
                throw new \Exception('Cannot resume a frozen subscription with no remaining days. Please renew it.');
            }

            $newEndDate = Carbon::today()->addDays($remainingDays);

            $latestSubscription->update([
                'status' => 'active',
                'resumed_at' => now(),
                'end_date' => $newEndDate->toDateString(),
            ]);

            User::where('id', $id)->update([
                'status' => 'active',
                'number_day' => $remainingDays,
            ]);

            $latestSubscription->refresh();

            return [
                'success' => true,
                'message' => 'Subscription resumed successfully',
                'remaining_days' => $remainingDays,
                'new_end_date' => $newEndDate->toDateString(),
                'subscription' => $latestSubscription,
            ];
        });
    }

    public function overview($id)
    {
        $this->syncExpiredSubscriptionStatusForUser((int) $id);

        $user = User::with(['Profile', 'goals', 'subscription'])->where('id', $id)->firstOrFail();
        $subscription = $user->subscription;

        return [
            'name' => $user->name,
            'email' => $user->email,
            'gender' => $user->Profile->gender ?? null,
            'age' => $user->Profile->age ?? null,
            'height' => $user->Profile->height ?? null,
            'weight' => $user->Profile->weight ?? null,
            'goal_type' => $user->goals->goal_type ?? null,
            'target_weight' => $user->goals->target_weight ?? null,
            'subscription_status' => $subscription?->status ?? $user->status,
            'end_date' => $subscription?->end_date?->toDateString(),
            'number_day' => $user->number_day ?? 0,
            'remaining_days' => $subscription?->remaining_days ?? 0,
            'frozen_remaining_days' => $subscription?->frozen_remaining_days,
            'frozen_at' => $subscription?->frozen_at,
            'resumed_at' => $subscription?->resumed_at,
            'subscription' => $subscription,
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
