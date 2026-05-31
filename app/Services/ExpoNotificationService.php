<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

        Log::info('Sending Expo push notification.', [
            'token' => self::maskToken($token),
            'title' => $title,
            'body_present' => filled($body),
            'data_keys' => array_keys($data),
        ]);

        $response = Http::timeout(15)->withHeaders([
            'Accept' => 'application/json',
            'Accept-encoding' => 'gzip, deflate',
            'Content-Type' => 'application/json',
        ])->post(self::EXPO_PUSH_URL, $message);

        $responsePayload = $response->json();

        Log::info('Expo push notification response received.', [
            'token' => self::maskToken($token),
            'http_status' => $response->status(),
            'successful_http' => $response->successful(),
            'response' => $responsePayload,
        ]);

        if (!$response->successful()) {
            Log::warning('Expo push notification HTTP request was not successful.', [
                'token' => self::maskToken($token),
                'http_status' => $response->status(),
                'response' => $responsePayload,
            ]);
        }

        return $response;
    }

    public static function maskToken(?string $token): string
    {
        if (!$token) {
            return '';
        }

        $length = strlen($token);

        if ($length <= 18) {
            return substr($token, 0, 4) . '...' . substr($token, -4);
        }

        return substr($token, 0, 14) . '...' . substr($token, -6);
    }
}
