<?php

namespace App\Services;

use App\Jobs\sendRegisterEmailJob;
use App\Models\User;
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
        $member = User::where('role_id', 1)->where('id', $id)->with('subscription')->get();
        return $member;
    }

    public function delete()
    {

    }
}
