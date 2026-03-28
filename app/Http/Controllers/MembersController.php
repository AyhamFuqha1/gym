<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubscriptionReques;
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
            $res = $this->memberService->store($request);
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

    public function reNewSubscription(Request $data)
    {
        try {
            $userId = auth()->id();
            $res = $this->memberService->reNewSubscription($data, $userId);
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
        return User::where("id", $id)->update(["status" => "frozen"]);
    }
    public function resumeSubscription($id)
    {
        return User::where("id", $id)->update(["status" => "cancel"]);
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

    public function nutrition($id){
         try {
            $res = $this->memberService->nutrition($id);
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

}
