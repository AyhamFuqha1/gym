<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CloseExpiredSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:close-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cheq subscriptions:close-expired';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notificationService)
    {
        $today = Carbon::today();

        $expiringSubscriptions = Subscription::where('status', 'active')
            ->whereDate('end_date', '<=', $today->toDateString())
            ->get();

        $expiredCount = $expiringSubscriptions->isEmpty()
            ? 0
            : Subscription::whereIn('id', $expiringSubscriptions->pluck('id')->all())
                ->update(['status' => 'expired']);

        foreach ($expiringSubscriptions as $subscription) {
            $subscription->status = 'expired';
            $this->notifySubscriptionExpired($notificationService, $subscription);
        }

        $userIds = Subscription::distinct()->pluck('user_id');

        foreach ($userIds as $userId) {
            $latestSubscription = Subscription::where('user_id', $userId)
                ->latest('id')
                ->first();

            if (!$latestSubscription) {
                continue;
            }

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

        $this->info("Expired {$expiredCount} active subscription(s).");
    }

    private function notifySubscriptionExpired(NotificationService $notificationService, Subscription $subscription): void
    {
        try {
            $recipientUserId = (int) $subscription->user_id;
            $endDate = $this->dateForPayload($subscription->end_date);
            $body = $endDate
                ? "Your FitMind subscription expired on {$endDate}."
                : 'Your FitMind subscription has expired.';

            $notificationService->notifyUser($recipientUserId, [
                'type' => 'subscription_expired',
                'title' => 'Subscription expired',
                'body' => $body,
                'entity_type' => 'subscription',
                'entity_id' => $subscription->id,
                'priority' => 'high',
                'channels' => ['in_app', 'push'],
                'data' => array_filter([
                    'screen' => 'Subscription',
                    'subscription_id' => $subscription->id,
                    'type' => 'subscription_expired',
                    'entity_type' => 'subscription',
                    'entity_id' => $subscription->id,
                    'status' => 'expired',
                    'start_date' => $this->dateForPayload($subscription->start_date),
                    'end_date' => $endDate,
                    'plan_id' => $subscription->plan_id,
                ], fn ($value) => $value !== null),
                'dedupe_key' => "subscription_expired:{$subscription->id}:{$recipientUserId}",
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to create subscription expired notification.', [
                'subscription_id' => $subscription->id,
                'recipient_user_id' => $subscription->user_id,
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
}
