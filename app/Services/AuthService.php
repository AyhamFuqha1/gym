<?php

namespace App\Services;

//use App\Jobs\SsendRegisterEmailJob;
use App\Models\User;
use DB;
use Hash;

class AuthService
{

    public function login($data)
    {
        return DB::transaction(function () use ($data) {
            $user = User::where('email', $data->email)->first();
            if (!$user || !Hash::check($data->password, $user->password)) {
                return [
                    "message" => "Email or password is incorrect",
                    'status' => 401
                ];
            } else {

                $token = $user->createToken('api-token')->plainTextToken;
                $role=DB::table("roles")->where("id",$user->role_id)->first();
                return [
                    "message" => "Login successful",
                    "status" => 200,
                    "role"   =>$role->name,
                    "token" => $token
                ];
            }
        });
    }

    public function register($data, $admin)
    {
        return DB::transaction(function () use ($data, $admin) {
            //$tempPassword = random_int(10000000, 99999999);
            $tempPassword = 123456;
            $userId = DB::table('users')->insertGetId(['name' => $data->name, 'email' => $data->email, 'password' => Hash::make($tempPassword), 'role_id' => $data->role_id]);
            //  SsendRegisterEmailJob::dispatch($data->email, $data->name, $tempPassword);
            return true;
        });
    }

    public function logout($user)
    {

        $user->currentAccessToken()->delete();

    }
}
