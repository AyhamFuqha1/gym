<?php

namespace App\Http\Controllers;

use App\Models\members;
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
          $res=$this->memberService->show($id);
          return response()->json($res,200);
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(members $members)
    {
        //
    }
}
