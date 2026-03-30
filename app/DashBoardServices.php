<?php

namespace App;

use App\Models\EquipmentReport;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use DB;

class DashBoardServices
{
    public function dashboard()
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $fiveDaysAgo = $now->copy()->subDays(5);
        $fiveWeeksAgo = $now->copy()->subWeeks(5);

        $totalMembers = User::where('role_id', 4)->count();

        $activeSubscriptions = Subscription::where('status', 'active')->count();

        $equmontCount = EquipmentReport::count();

        $monyInMonth = Subscription::join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->whereBetween('subscriptions.created_at', [$startOfMonth, $now])
            ->sum('plans.price');

        $subscriptionsByDay = Subscription::where('created_at', '>=', $fiveDaysAgo)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        $RecentIssues = EquipmentReport::orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $weeklySubscriptions = Subscription::selectRaw('YEARWEEK(created_at, 1) as week, COUNT(*) as users_count')
            ->where('created_at', '>=', $fiveWeeksAgo)
            ->groupBy('week')
            ->orderBy('week', 'asc')
            ->get();

        return [
            'totalMembers' => $totalMembers,
            'activeSubscriptions' => $activeSubscriptions,
            'equmont' => $equmontCount,
            'monyInMonth' => (string) $monyInMonth,
            'subscriptionsByDay' => $subscriptionsByDay,
            'RecentIssues' => $RecentIssues,
            'weeklySubscriptions' => $weeklySubscriptions,
            'last_updated' => now()->toDateTimeString(),
        ];
    }
}