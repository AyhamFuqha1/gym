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
                    "status" => 401
                ];
            }

            $token = $user->createToken('api-token')->plainTextToken;
            $role = DB::table("roles")->where("id", $user->role_id)->first();

            return [
                "message" => "Login successful",
                "status" => 200,
                "role" => $role->name,
                "token" => $token,
                "user_id" => $user->id,
                "user_name" => $user->name,
                "email" => $user->email
            ];
        });
    }

    /*public function register($data, $admin)
    {
        return DB::transaction(function () use ($data, $admin) {
            //$tempPassword = random_int(10000000, 99999999);
            $tempPassword = 123456;
            $userId = DB::table('users')->insertGetId(['name' => $data->name, 'email' => $data->email, 'password' => Hash::make($tempPassword), 'role_id' => $data->role_id,'fingerprint' => $data->fingerprint ?? null]);
            //  SsendRegisterEmailJob::dispatch($data->email, $data->name, $tempPassword);
            return true;
        });
    }*/

   public function register($data, $currentUser)
    {
        return DB::transaction(function () use ($data, $currentUser) {
            $currentRoleId = (int) $currentUser->role_id;
            $targetRoleId = (int) $data->role_id;

            $allowedRoles = [
                1 => [2, 3, 4], // manager -> admin, coach, user
                2 => [3, 4],    // admin -> coach, user
                3 => [4],       // coach -> user
                4 => [],        // user -> nobody
            ];

            if (!isset($allowedRoles[$currentRoleId])) {
                return [
                    'status' => 403,
                    'message' => 'Your role is not allowed to create users'
                ];
            }

            if (!in_array($targetRoleId, $allowedRoles[$currentRoleId], true)) {
                return [
                    'status' => 403,
                    'message' => 'You are not allowed to create this role'
                ];
            }

            $tempPassword = 123456;

            DB::table('users')->insert([
                'name' => $data->name,
                'email' => $data->email,
                'password' => Hash::make($tempPassword),
                'role_id' => $targetRoleId,
                'status' => 'active',
                'created_at' => now(),
            ]);

            return [
                'status' => 200,
                'message' => 'User created successfully'
            ];
        });
    }    

    public function logout($user)
    {
        $user->currentAccessToken()->delete();

        return [
            'status' => 200,
            'message' => 'Logout successful'
        ];
    }

    public function changePassword($user, array $data)
    {
        if (!Hash::check($data['current_password'], $user->password)) {
            return [
                'status' => 422,
                'message' => 'Current password is incorrect.'
            ];
        }

        $user->password = Hash::make($data['password']);
        $user->save();
        $user->currentAccessToken()->delete();

        return [
            'status' => 200,
            'message' => 'Password changed successfully. Please login again.'
        ];
    }

    /*public function forgotPassword($email)
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

    }*/


    public function forgotPassword($email)
    {
        $OTP = random_int(10000000, 99999999);
        $user = DB::table("users")->where("email", $email)->first();

        if (!$user) {
            return false;
        }

        DB::table("password_reset_tokens")->where("email", $email)->delete();

        DB::table("password_reset_tokens")->insert([
            "email" => $email,
            "token" => $OTP,
            "created_at" => now(),
        ]);

        SendOTPJob::dispatch($email, $user->name, $OTP);

        return true;
    }
    /*public function verifyOTP($OTP, $email)
    {
        $res = DB::table("password_reset_tokens")->where("email", $email)->where("token", $OTP)->where("created_at", ">", now()->subMinutes(2))->first();
        if (!$res) {
            return false;
        } else {
            DB::table("password_reset_tokens")->where("email", $email)->where("token", $OTP)->where("created_at", ">", now()->subMinutes(2))->delete();
            return true;

        }

    }*/

    public function verifyOTP($OTP, $email)
    {
        $res = DB::table("password_reset_tokens")
            ->where("email", $email)
            ->where("token", $OTP)
            ->where("created_at", ">", now()->subMinutes(2))
            ->first();

        if (!$res) {
            return false;
        }

        $resetToken = bin2hex(random_bytes(32));

        DB::table("password_reset_tokens")
            ->where("email", $email)
            ->update([
                "token" => $resetToken,
                "created_at" => now(),
            ]);

        return $resetToken;
    }
    /*public function resetPassword($restetPasseord, $email)
    {
        return
            DB::transaction(function () use ($restetPasseord, $email) {
                DB::table("users")->where("email", $email)->update(["password" => Hash::make($restetPasseord)]);
                DB::table("password_reset_tokens")->where("email", $email)->delete();
                return true;
            });

    }*/

    public function resetPassword($password, $resetToken)
    {
        return DB::transaction(function () use ($password, $resetToken) {
            $record = DB::table("password_reset_tokens")
                ->where("token", $resetToken)
                ->first();

            if (!$record) {
                return false;
            }

            DB::table("users")
                ->where("email", $record->email)
                ->update([
                    "password" => Hash::make($password)
                ]);

            DB::table("password_reset_tokens")
                ->where("email", $record->email)
                ->delete();

            return true;
        });
    }

}
