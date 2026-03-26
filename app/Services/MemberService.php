<?php

namespace App\Services;

use App\Jobs\sendRegisterEmailJob;
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


        return response()->json([
            'stats' => $stats,
            'members' => $members
        ]);
    }
    public function store($data, $admin)
    {
        return DB::transaction(function () use ($data, $admin) {
            $tempPassword = random_int(10000000, 99999999);
            $userId = DB::table('users')->insertGetId(['name' => $data->name, 'email' => $data->email, 'password' => Hash::make($tempPassword), 'role_id' => $data->role_id]);
            sendRegisterEmailJob::dispatch($data->email, $data->name, $tempPassword);
            return true;
        });
    }

    public function show($id)
{
   
    $user = User::where('role_id', 1)
                ->with(['subscription.plan']) 
                ->findOrFail($id);

    return [
        "member" => [
            "id"           => $user->id,
            "name"         => $user->name,
            "email"        => $user->email,
            "phone"        => $user->number_phone,
            "status"       => $user->status,    
            "is_frozen"    => (bool) $user->is_frozen,
            "joined_at"    => $user->created_at->format('Y-m-d'),
            "remaining_days" => $user->number_day, 
        ],

   
        "subscriptions" => $user->subscription->map(function ($sub) {
            return [
                "id"         => $sub->id,
                "plan_name"  => $sub->plan->name ?? 'N/A',
                "price"      => $sub->plan->price ?? 0,
                "discount"   => $sub->discount ?? 0,
                "start_date" => $sub->start_date,
                "end_date"   => $sub->end_date,
                "status"     => $sub->status,
                "created_at" => $sub->created_at->format('Y-m-d'),
            ];
        }),

      
        "payment_summary" => [
            "total_paid" => $user->subscription->sum(function ($sub) {
                return ($sub->plan->price ?? 0) - ($sub->discount ?? 0);
            }),
        ],
    ];
}
    public function reNewSubscription($data, $user_id)
    {
        $plan = Plan::where('id', $data->plan_id)->first();
        $startDate = $data->start_date ? Carbon::parse($data->start_date) : now();
        $endDate = $startDate->copy()->addDays($plan->duration_days);
        $Sub = [
            'user_id' => $user_id,
            'plan_id' => $data->plan_id,
            'discount' => $data->discount,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => "active"
        ];
        
         Subscription::create($Sub);
         $number_day=$plan->duration_days;
         User::where("id",$user_id)->increment("number_day",$number_day);
         return $Sub;
    }

    public function freezeSubscription($id){

    }
    
    public function resumeSubscription($id){

    }
}
