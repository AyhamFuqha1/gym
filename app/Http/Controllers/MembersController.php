<?php

namespace App\Http\Controllers;

use App\Models\members;
use App\Models\User;
use App\Services\MemberService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Throwable;

class MembersController extends Controller
{
    private MemberService $memberService;

    public function __construct(MemberService $memberService)
    {
        $this->memberService = $memberService;
    }

    public function index()
    {
        try {
            $res = $this->memberService->index();
            return response()->json($res, 200);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required|string|min:3|max:255',
                'email' => 'required|email|unique:users,email',
                'role_id' => 'required|integer|exists:roles,id',
            ]);

            $authUser = $request->user();

            if (!$authUser) {
                return response()->json([
                    'message' => 'Unauthorized'
                ], 401);
            }

            if (!in_array($authUser->role_id, [1, 2])) {
                return response()->json([
                    'message' => 'Forbidden. Only admin or manager can create members.'
                ], 403);
            }

            $res = $this->memberService->store((object) $data, $authUser);

            return response()->json($res, 201);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $res = $this->memberService->show($id);
            return response()->json($res, 200);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    public function reNewSubscription(Request $request)
    {
        try {
            $data = $request->validate([
                'user_id' => 'required|integer|exists:users,id',
                'plan_id' => 'required|integer|exists:plans,id',
                'discount' => 'nullable|numeric|min:0',
                'start_date' => 'nullable|date',
            ]);

            $createdBy = Auth::id();
            $res = $this->memberService->reNewSubscription((object) $data, $createdBy);

            return response()->json($res, 200);
        } catch (Throwable $e) {
            $statusCode = $e->getMessage() === 'This member already has an active subscription.'
                ? 409
                : 500;

            return response()->json([
                'message' => $e->getMessage(),
            ], $statusCode);
        }
    }

    public function freezeSubscription($id)
    {
        try {
            $res = $this->memberService->freezeSubscription($id);
            return response()->json($res, 200);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    public function resumeSubscription($id)
    {
        try {
            $res = $this->memberService->resumeSubscription($id);
            return response()->json($res, 200);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    public function overview($id)
    {
        try {
            $res = $this->memberService->overview($id);
            return response()->json($res, 200);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    public function nutrition($id)
    {
        try {
            $res = $this->memberService->nutrition($id);
            return response()->json($res, 200);
        } catch (Throwable $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    public function update(Request $request, members $members)
    {
        //
    }

    public function destroy(members $members)
    {
        //
    }
}