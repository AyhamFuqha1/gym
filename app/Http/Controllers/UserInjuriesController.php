<?php

namespace App\Http\Controllers;

use App\Models\UserInjuries;
use App\Services\UserInjuriesService;
use Illuminate\Http\Request;
use Log;

class UserInjuriesController extends Controller
{

    private UserInjuriesService $userInjuriesService;

    public function __construct(UserInjuriesService $userInjuriesService)
    {
        $this->userInjuriesService = $userInjuriesService;
    }

    public function index()
    {
        $res = $this->userInjuriesService->index();

        return response()->json([
            'success' => true,
            'data' => $res
        ], 200);
    }

    public function store(Request $request)
    {
        $res = $this->userInjuriesService->store($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Injuries created successfully',
            'data' => $res
        ], 201);
    }

    public function show($id)
    {
        $Injuries = $this->userInjuriesService->show($id);
        if (!$Injuries) {
            return response()->json([
                'success' => false,
                'message' => 'Profile not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $Injuries
        ]);

    }

    public function update(Request $request, $id)
    {
        $Injuries = $this->userInjuriesService->update($id, $request->all());
        if (!$Injuries) {
            return response()->json([
                'success' => false,
                'message' => 'Injurie not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Injurie updated successfully',
            'data' => $Injuries
        ]);

    }

    public function destroy($id)
    {
        $deleted = $this->userInjuriesService->destroy($id);

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Injurie not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Injurie deleted successfully'
        ]);
    }

    public function dashboard()
    {
        $res = $this->userInjuriesService->dashboard();
        return response()->json($res, 200);
    }
}
