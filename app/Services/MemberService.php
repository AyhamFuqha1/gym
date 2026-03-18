<?php

namespace App\Services;

use App\Jobs\sendRegisterEmailJob;
use DB;
use Hash;

class MemberService
{
    public function index()
    {
        // 1. جلب البيانات الأساسية (نفس كودك السابق)
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
        $res = DB::select("
        SELECT 
            p.*,

            COALESCE(JSON_ARRAYAGG(DISTINCT i.injury_type), JSON_ARRAY()) as injuries,
            COALESCE(JSON_ARRAYAGG(DISTINCT g.goal_type), JSON_ARRAY()) as goals

        FROM user_profile p
        LEFT JOIN user_injuries i ON p.user_id = i.user_id
        LEFT JOIN user_goals g ON p.user_id = g.user_id

        WHERE p.user_id = ?

        GROUP BY p.user_id
    ", [$id]);
    }

    public function delete()
    {

    }
}
