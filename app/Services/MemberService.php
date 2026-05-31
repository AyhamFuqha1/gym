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
use Illuminate\Support\Facades\Log;

class MemberService
{
    private const SUBSCRIPTION_REQUIRED_TITLE = 'Subscription Required';
    private const SUBSCRIPTION_REQUIRED_MESSAGE = 'Your subscription is not active. Please renew your subscription to continue.';

    public function __construct(private NotificationService $notificationService)
    {
        //
    }

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

            $this->notifySubscriptionRenewed($createdSubscription);

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

            $this->notifySubscriptionFrozen($latestSubscription);

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

            $result = $this->resumeFrozenSubscription($latestSubscription);

            return [
                'success' => true,
                'message' => 'Subscription resumed successfully',
                'remaining_days' => $result['remaining_days'],
                'new_end_date' => $result['new_end_date'],
                'subscription' => $result['subscription'],
            ];
        });
    }

    public function ensureMemberSubscriptionAccess(User $user): array
    {
        if ((int) $user->role_id !== 4) {
            return [
                'allowed' => true,
                'auto_resumed' => false,
                'subscription' => null,
            ];
        }

        return DB::transaction(function () use ($user) {
            $subscription = Subscription::where('user_id', $user->id)
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (!$subscription) {
                User::where('id', $user->id)->update([
                    'status' => 'expired',
                    'number_day' => 0,
                ]);

                return $this->subscriptionRequiredResponse(null, 'missing');
            }

            $today = Carbon::today();

            if (
                $subscription->status === 'active' &&
                (!$subscription->end_date || Carbon::parse($subscription->end_date)->startOfDay()->lte($today))
            ) {
                $subscription->update(['status' => 'expired']);
                $subscription->refresh();
            }

            if ($subscription->status === 'frozen') {
                $remainingDays = max((int) ($subscription->frozen_remaining_days ?? 0), 0);

                if ($remainingDays <= 0) {
                    User::where('id', $user->id)->update([
                        'status' => 'frozen',
                        'number_day' => 0,
                    ]);

                    return $this->subscriptionRequiredResponse($subscription, 'frozen_no_remaining_days');
                }

                $result = $this->resumeFrozenSubscription(
                    $subscription,
                    'Your subscription was frozen and has now been reactivated because you opened the app.',
                    "subscription_resumed:auto:{$subscription->id}:{$user->id}"
                );

                return [
                    'allowed' => true,
                    'auto_resumed' => true,
                    'subscription' => $result['subscription'],
                ];
            }

            if ($subscription->status === 'active') {
                $remainingDays = $subscription->remaining_days;

                User::where('id', $user->id)->update([
                    'status' => 'active',
                    'number_day' => $remainingDays,
                ]);

                return [
                    'allowed' => true,
                    'auto_resumed' => false,
                    'subscription' => $subscription,
                ];
            }

            User::where('id', $user->id)->update([
                'status' => $subscription->status ?: 'expired',
                'number_day' => 0,
            ]);

            return $this->subscriptionRequiredResponse($subscription, $subscription->status ?: 'inactive');
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

    private function notifySubscriptionRenewed(Subscription $subscription): void
    {
        $endDate = $this->dateForPayload($subscription->end_date);
        $body = $endDate
            ? "Your FitMind subscription is active until {$endDate}."
            : 'Your FitMind subscription has been renewed.';

        $this->notifySubscriptionEvent(
            $subscription,
            'subscription_renewed',
            'Subscription renewed',
            $body
        );
    }

    private function notifySubscriptionFrozen(Subscription $subscription): void
    {
        $remainingDays = (int) ($subscription->frozen_remaining_days ?? 0);
        $body = $remainingDays > 0
            ? "Your FitMind subscription is frozen with {$remainingDays} day(s) remaining."
            : 'Your FitMind subscription has been frozen.';

        $this->notifySubscriptionEvent(
            $subscription,
            'subscription_frozen',
            'Subscription frozen',
            $body
        );
    }

    private function resumeFrozenSubscription(
        Subscription $subscription,
        ?string $notificationBody = null,
        ?string $dedupeKey = null
    ): array {
        $remainingDays = max((int) ($subscription->frozen_remaining_days ?? 0), 0);

        if ($remainingDays <= 0) {
            throw new \Exception('Cannot resume a frozen subscription with no remaining days. Please renew it.');
        }

        $newEndDate = Carbon::today()->addDays($remainingDays);

        $subscription->update([
            'status' => 'active',
            'resumed_at' => now(),
            'end_date' => $newEndDate->toDateString(),
        ]);

        User::where('id', $subscription->user_id)->update([
            'status' => 'active',
            'number_day' => $remainingDays,
        ]);

        $subscription->refresh();

        $this->notifySubscriptionResumed($subscription, $notificationBody, $dedupeKey);

        return [
            'remaining_days' => $remainingDays,
            'new_end_date' => $newEndDate->toDateString(),
            'subscription' => $subscription,
        ];
    }

    private function notifySubscriptionResumed(
        Subscription $subscription,
        ?string $body = null,
        ?string $dedupeKey = null
    ): void
    {
        $endDate = $this->dateForPayload($subscription->end_date);
        $body = $body ?? ($endDate
            ? "Your FitMind subscription has resumed and is active until {$endDate}."
            : 'Your FitMind subscription has resumed.');

        $this->notifySubscriptionEvent(
            $subscription,
            'subscription_resumed',
            'Subscription resumed',
            $body,
            $dedupeKey
        );
    }

    private function notifySubscriptionEvent(
        Subscription $subscription,
        string $type,
        string $title,
        string $body,
        ?string $dedupeKey = null
    ): void {
        try {
            $recipientUserId = (int) $subscription->user_id;

            $this->notificationService->notifyUser($recipientUserId, [
                'actor_user_id' => auth()->id(),
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'entity_type' => 'subscription',
                'entity_id' => $subscription->id,
                'priority' => 'high',
                'channels' => ['in_app', 'push'],
                'data' => array_filter([
                    'screen' => 'Subscription',
                    'subscription_id' => $subscription->id,
                    'type' => $type,
                    'entity_type' => 'subscription',
                    'entity_id' => $subscription->id,
                    'status' => $subscription->status,
                    'start_date' => $this->dateForPayload($subscription->start_date),
                    'end_date' => $this->dateForPayload($subscription->end_date),
                    'plan_id' => $subscription->plan_id,
                    'remaining_days' => $subscription->remaining_days ?? null,
                    'frozen_remaining_days' => $subscription->frozen_remaining_days,
                ], fn ($value) => $value !== null),
                'dedupe_key' => $dedupeKey ?? "{$type}:{$subscription->id}:{$recipientUserId}",
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to create subscription notification.', [
                'subscription_id' => $subscription->id,
                'recipient_user_id' => $subscription->user_id,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function dateForPayload($date): ?string
    {
        if (!$date) {
            return null;
        }

        if ($date instanceof \DateTimeInterface) {
            return $date->format('Y-m-d');
        }

        return (string) $date;
    }

    private function subscriptionRequiredResponse(?Subscription $subscription, string $reason): array
    {
        return [
            'allowed' => false,
            'auto_resumed' => false,
            'status' => 403,
            'code' => 'subscription_required',
            'title' => self::SUBSCRIPTION_REQUIRED_TITLE,
            'message' => self::SUBSCRIPTION_REQUIRED_MESSAGE,
            'subscription_status' => $subscription?->status ?? $reason,
            'subscription_id' => $subscription?->id,
            'renew_required' => true,
        ];
    }
}
