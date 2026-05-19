<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\resetPasswordRequest;
use App\Http\Requests\verifyOTPRequest;
use App\Models\User;
use App\Services\AuthService;
//use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Throwable;

class AuthController extends Controller
{
    private AuthService $authService;
    //  use AuthorizesRequests;

    public function __construct(AuthService $AuthService)
    {
        $this->authService = $AuthService;
    }

    public function login(LoginRequest $request)
    {
        try {
            $login = $this->authService->login($request);

            if ($login['status'] !== 200) {
                return response()->json(
                    ['message' => $login['message']],
                    $login['status']
                );
            }

            return response()->json([
                'message' => $login['message'],
                'token' => $login['token'],
                'role' => $login['role'],
                'user_id' => $login['user_id'],
                'user_name' => $login['user_name'],
                'email' => $login['email'],
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    public function register(RegisterRequest $request)
    {
        try {
            $user = $request->user();/////////////

            if (!$user) {
                return response()->json([
                    'message' => 'Unauthorized'
                ], 401);
            }

            $register = $this->authService->register($request, $user);

            if ($register['status'] !== 200) {
                return response()->json([
                    'message' => $register['message']
                ], $register['status']);
            }

            return response()->json([
                'message' => $register['message']
            ], 200);

        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'message' => 'Unauthorized'
                ], 401);
            }

            $logout = $this->authService->logout($user);

            return response()->json([
                'message' => $logout['message']
            ], $logout['status']);

        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'message' => 'Unauthorized'
                ], 401);
            }

            $result = $this->authService->changePassword($user, $request->validated());

            return response()->json([
                'message' => $result['message']
            ], $result['status']);

        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    public function forgotPassword(Request $request)
    {
       $request->validate([
        "email" => "required|email"
       ]);
        try {
            $res = $this->authService->forgotPassword($request->email);
            if (!$res) {
                return response()->json([
                    "message" => "Email Not Corect"
                ], 404);
            } else {
                return response()->json([
                    "message" => "Send Email"
                ], 200);
            }

        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    /*public function verifyOTP(verifyOTPRequest $request)
    {
        try {
            $res = $this->authService->verifyOTP($request->OTP,$request->email);
            if (!$res) {
                return response()->json([
                    "message" => "OTP in incorrect"
                ], 403);
            } else {
                return response()->json([
                    "message" => "OTP is correct"
                ], 200);
            }
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }*/

    public function verifyOTP(verifyOTPRequest $request)
    {
        try {
            $resetToken = $this->authService->verifyOTP($request->OTP, $request->email);

            if (!$resetToken) {
                return response()->json([
                    "message" => "OTP is incorrect"
                ], 403);
            }

            return response()->json([
                "message" => "OTP is correct",
                "reset_token" => $resetToken
            ], 200);

        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }    
    /*public function restetPassword(resetPasswordRequest $request)
    {
        try {
            $res = $this->authService->resetPassword($request->password, $request->email);
            if (!$res) {
                return response()->json([
                    "message" => "Not Update Password"
                ], 403);
            } else {
                return response()->json([
                    "message" => "Update Password"
                ], 201);
            }
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }*/


    public function restetPassword(resetPasswordRequest $request)
    {
        try {
            $res = $this->authService->resetPassword(
                $request->password,
                $request->reset_token
            );

            if (!$res) {
                return response()->json([
                    "message" => "Invalid reset token"
                ], 403);
            }

            return response()->json([
                "message" => "Update Password"
            ], 200);

        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }
}
