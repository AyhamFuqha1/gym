<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class ExpoNotificationService
{
    private const EXPO_PUSH_URL = 'https://exp.host/--/api/v2/push/send';

    public static function send($token, $title, $body, $data = []): Response
    {
        return app(self::class)->sendToToken($token, $title, $body, $data);
    }

    public function sendToToken(string $token, string $title, ?string $body = null, array $data = []): Response
    {
        $message = [
            'to' => $token,
            'title' => $title,
            'body' => $body ?? '',
            'data' => $data,
            'sound' => 'default',
        ];

        return Http::withHeaders([
            'Accept' => 'application/json',
            'Accept-encoding' => 'gzip, deflate',
            'Content-Type' => 'application/json',
        ])->post(self::EXPO_PUSH_URL, $message);
    }
}
