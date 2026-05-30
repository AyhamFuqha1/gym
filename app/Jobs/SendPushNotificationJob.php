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

                if ($this->isInvalidTokenResponse($responsePayload)) {
                    $token->update([
                        'is_active' => false,
                        'revoked_at' => now(),
                    ]);
                }

                if (!$response->successful()) {
                    Log::warning('Expo push request failed.', [
                        'notification_id' => $notification->id,
                        'push_token_id' => $token->id,
                        'status' => $response->status(),
                        'response' => $responsePayload,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('Push notification send failed.', [
                    'notification_id' => $notification->id,
                    'push_token_id' => $token->id,
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
            return str_contains($payload, 'DeviceNotRegistered')
                || str_contains($payload, 'not a registered push notification recipient');
        }

        if (!is_array($payload)) {
            return false;
        }

        foreach ($payload as $value) {
            if ($this->isInvalidTokenResponse($value)) {
                return true;
            }
        }

        return false;
    }
}
