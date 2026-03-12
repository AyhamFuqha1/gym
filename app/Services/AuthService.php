<?php

namespace App\Services;

//use App\Jobs\SsendRegisterEmailJob;
use App\Jobs\SendOTPJob;
use App\Models\User;
use DB;
use Hash;
use Log;

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
                $role = DB::table("roles")->where("id", $user->role_id)->first();
                return [
                    "message" => "Login successful",
                    "status" => 200,
                    "role" => $role->name,
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

    public function forgotPassword($email)
    {
        $OTP = random_int(10000000, 99999999);
        $user = DB::table("users")->where("email", $email)->first();
        Log::info($email);
        if (!$user) { 
            return false;
        } else {
            DB::table("password_reset_tokens")->insert(["email"=>$email,"token"=>$OTP]);
            SendOTPJob::dispatch($email, $user->name, $OTP);
            return true;
        }

    }
    public function verifyOTP($OTP, $email)
    {
        $res = DB::table("password_reset_tokens")->where("email", $email)->where("token", $OTP)->where("created_at", ">", now()->subMinutes(2))->first();
        if (!$res) {
            return false;
        } else {
            DB::table("password_reset_tokens")->where("email", $email)->where("token", $OTP)->where("created_at", ">", now()->subMinutes(2))->delete();
            return true;

        }

    }
    public function resetPassword($restetPasseord, $email)
    {
        return
            DB::transaction(function () use ($restetPasseord, $email) {
                DB::table("users")->where("email", $email)->update(["password" => Hash::make($restetPasseord)]);
                DB::table("password_reset_tokens")->where("email", $email)->delete();
                return true;
            });

    }
}
