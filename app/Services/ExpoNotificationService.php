<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ExpoNotificationService
{
    public static function send($token, $title, $body, $data = [])
    {
        $message = [
            'to' => $token,
            'sound' => 'default',
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ];

        return Http::withHeaders([
            'Accept' => 'application/json',
            'Accept-encoding' => 'gzip, deflate',
            'Content-Type' => 'application/json',
        ])->post('https://exp.host/--/api/v2/push/send', $message);
    }
}
