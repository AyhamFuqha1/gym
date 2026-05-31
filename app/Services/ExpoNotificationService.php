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

        $response = Http::timeout(15)->withHeaders([
            'Accept' => 'application/json',
            'Accept-encoding' => 'gzip, deflate',
            'Content-Type' => 'application/json',
        ])->post(self::EXPO_PUSH_URL, $message);

        $responsePayload = $response->json();

        if (!$response->successful()) {
            Log::warning('Expo push notification HTTP request was not successful.', [
                'token' => self::maskToken($token),
                'http_status' => $response->status(),
                'expo_status' => $this->expoStatus($responsePayload),
                'expo_error_codes' => $this->expoErrorCodes($responsePayload),
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

    private function expoStatus(mixed $payload): ?string
    {
        if (!is_array($payload)) {
            return null;
        }

        if (is_string($payload['status'] ?? null)) {
            return $payload['status'];
        }

        if (is_array($payload['data'] ?? null)) {
            return $this->expoStatus($payload['data']);
        }

        return null;
    }

    private function expoErrorCodes(mixed $payload): array
    {
        $codes = [];
        $this->collectExpoErrorCodes($payload, $codes);

        return array_values(array_unique($codes));
    }

    private function collectExpoErrorCodes(mixed $payload, array &$codes): void
    {
        if (!is_array($payload)) {
            return;
        }

        if (is_string($payload['error'] ?? null)) {
            $codes[] = $payload['error'];
        }

        if (is_array($payload['details'] ?? null)) {
            $this->collectExpoErrorCodes($payload['details'], $codes);
        }

        foreach ($payload as $value) {
            if (is_array($value)) {
                $this->collectExpoErrorCodes($value, $codes);
            }
        }
    }
}
