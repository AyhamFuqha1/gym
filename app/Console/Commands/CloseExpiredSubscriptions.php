<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

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
    public function handle()
    {
        $today = Carbon::today();

        $expiredCount = Subscription::where('status', 'active')
            ->whereDate('end_date', '<=', $today->toDateString())
            ->update(['status' => 'expired']);

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
}
