<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
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
            } else {
                return response()->json([
                    'message' => $login['message'],
                    'token' => $login['token'],
                    'role' => $login['role']
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

    public function register(RegisterRequest $request)
    {
        try {
            //       $this->authorize('create', User::class);
            $admin = ["id" => 1];
            $register = $this->authService->register($request, $admin);
            if ($register) {
                return response()->json(true, 200);
            } else {
                return response()->json(false, 500);
            }
        } catch (AuthorizationException $e) {
            return response()->json(
                ['message' => $e->getMessage()],
                403
            );
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
        //$user = $request->user();
        $user = ["id" => 1];
        try {
            $logout = $this->authService->logout($user);
            if ($logout['status'] == 200) {
                return response()->json($logout['message'], 200);
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

    public function verifyOTP(verifyOTPRequest $request)
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
    }
    public function restetPassword(resetPasswordRequest $request)
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
    }
}
