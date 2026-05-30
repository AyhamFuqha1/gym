<?php

namespace App\Http\Controllers;

use App\Models\PushTokens;
use Illuminate\Http\Request;

class PushTokensController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'token' => 'required|string|max:2048',
            'provider' => 'sometimes|string|max:50',
            'platform' => 'sometimes|nullable|string|max:50',
            'device_id' => 'sometimes|nullable|string|max:255',
            'app_version' => 'sometimes|nullable|string|max:50',
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        $pushToken = PushTokens::updateOrCreate(
            ['token' => $data['token']],
            [
                'user_id' => $user->id,
                'provider' => $data['provider'] ?? 'expo',
                'platform' => $data['platform'] ?? null,
                'device_id' => $data['device_id'] ?? null,
                'app_version' => $data['app_version'] ?? null,
                'is_active' => true,
                'last_seen_at' => now(),
                'revoked_at' => null,
            ]
        );

        return response()->json([
            'message' => 'Push token stored successfully',
            'data' => [
                'id' => $pushToken->id,
                'provider' => $pushToken->provider,
                'platform' => $pushToken->platform,
                'last_seen_at' => optional($pushToken->last_seen_at)->toDateTimeString(),
            ],
        ], 200);
    }
}
