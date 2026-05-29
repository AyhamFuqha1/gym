<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PushTokensController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'token' => 'required|string',
        ]);

        $user = $request->user();

        $user->pushTokens()->updateOrCreate(
            ['token' => $data['token']],
            []
        );

        return response()->json([
            'message' => 'Push token stored successfully',
        ], 200);
    }
}
