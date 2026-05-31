<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\PushTokens;
use App\Services\ExpoNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendPushNotificationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(private readonly int $notificationId)
    {
        //
    }

    public function handle(ExpoNotificationService $expoNotificationService): void
    {
        $notification = Notification::find($this->notificationId);

        if (!$notification || !$notification->recipient_user_id) {
            return;
        }

        $tokens = PushTokens::query()
            ->where('user_id', $notification->recipient_user_id)
            ->where('is_active', true)
            ->get();

        if ($tokens->isEmpty()) {
            return;
        }

        $attempted = false;

        foreach ($tokens as $token) {
            try {
                $attempted = true;

                $response = $expoNotificationService->sendToToken(
                    $token->token,
                    $notification->title,
                    $notification->body,
                    $this->buildPushData($notification)
                );

                $responsePayload = $response->json();
                $expoErrorCodes = $this->expoErrorCodes($responsePayload);

                if ($this->isInvalidTokenResponse($responsePayload)) {
                    $token->update([
                        'is_active' => false,
                        'revoked_at' => now(),
                    ]);

                    Log::warning('Push token deactivated because Expo reported it invalid.', [
                        'notification_id' => $notification->id,
                        'push_token_id' => $token->id,
                        'token' => ExpoNotificationService::maskToken($token->token),
                        'expo_error_codes' => $expoErrorCodes,
                    ]);
                }

                if (!$response->successful() || $this->hasExpoErrorResponse($responsePayload)) {
                    Log::warning('Expo push request returned an error response.', [
                        'notification_id' => $notification->id,
                        'push_token_id' => $token->id,
                        'token' => ExpoNotificationService::maskToken($token->token),
                        'http_status' => $response->status(),
                        'successful_http' => $response->successful(),
                        'expo_status' => $this->expoStatus($responsePayload),
                        'expo_error_codes' => $expoErrorCodes,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('Push notification send failed.', [
                    'notification_id' => $notification->id,
                    'push_token_id' => $token->id,
                    'token' => ExpoNotificationService::maskToken($token->token),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($attempted) {
            $notification->update([
                'sent_at' => now(),
            ]);
        }
    }

    private function buildPushData(Notification $notification): array
    {
        return array_filter(
            array_merge($notification->data ?? [], [
                'type' => $notification->type,
                'entity_type' => $notification->entity_type,
                'entity_id' => $notification->entity_id,
                'notification_id' => $notification->id,
            ]),
            fn ($value) => $value !== null
        );
    }

    private function isInvalidTokenResponse(mixed $payload): bool
    {
        if (is_string($payload)) {
            $lowerPayload = strtolower($payload);

            return str_contains($payload, 'DeviceNotRegistered')
                || str_contains($lowerPayload, 'not a registered push notification recipient')
                || str_contains($lowerPayload, 'invalid push token');
        }

        if (!is_array($payload)) {
            return false;
        }

        if (in_array('DeviceNotRegistered', $this->expoErrorCodes($payload), true)) {
            return true;
        }

        foreach ($payload as $value) {
            if ($this->isInvalidTokenResponse($value)) {
                return true;
            }
        }

        return false;
    }

    private function hasExpoErrorResponse(mixed $payload): bool
    {
        if (is_string($payload)) {
            return str_contains(strtolower($payload), 'error');
        }

        if (!is_array($payload)) {
            return false;
        }

        if (($payload['status'] ?? null) === 'error') {
            return true;
        }

        foreach ($payload as $value) {
            if ($this->hasExpoErrorResponse($value)) {
                return true;
            }
        }

        return false;
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
