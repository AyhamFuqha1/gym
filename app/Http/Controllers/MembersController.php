<?php

namespace App\Http\Controllers;

use App\Models\members;
use App\Models\User;
use App\Services\MemberService;
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

            $admin = $request->user() ?? (object)['id' => 1];

            $res = $this->memberService->store((object) $data, $admin);

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

            $res = $this->memberService->reNewSubscription((object) $data);

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