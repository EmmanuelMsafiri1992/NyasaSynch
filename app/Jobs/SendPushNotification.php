<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public User $user;
    public string $title;
    public string $body;
    public array $data;

    public function __construct(User $user, string $title, string $body, array $data = [])
    {
        $this->user = $user;
        $this->title = $title;
        $this->body = $body;
        $this->data = $data;
    }

    public function handle(): void
    {
        try {
            // Get user's push notification tokens
            $tokens = $this->getUserPushTokens();

            if (empty($tokens)) {
                return;
            }

            // Send to Firebase Cloud Messaging
            $this->sendFCMNotification($tokens);

            // Send to Apple Push Notification Service
            $this->sendAPNSNotification($tokens);

            Log::info('Push notification sent successfully', [
                'user_id' => $this->user->id,
                'title' => $this->title
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send push notification', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function getUserPushTokens(): array
    {
        // This would get tokens from user preferences/devices table
        // For now, return empty array as we haven't implemented device management
        return [];
    }

    private function sendFCMNotification(array $tokens): void
    {
        $fcmServerKey = config('services.fcm.server_key');

        if (!$fcmServerKey) {
            return;
        }

        $notification = [
            'title' => $this->title,
            'body' => $this->body,
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
        ];

        $data = array_merge($this->data, [
            'type' => 'message',
            'timestamp' => now()->toISOString()
        ]);

        $payload = [
            'registration_ids' => $tokens,
            'notification' => $notification,
            'data' => $data,
            'priority' => 'high'
        ];

        Http::withHeaders([
            'Authorization' => 'key=' . $fcmServerKey,
            'Content-Type' => 'application/json'
        ])->post('https://fcm.googleapis.com/fcm/send', $payload);
    }

    private function sendAPNSNotification(array $tokens): void
    {
        $apnsKey = config('services.apns.key');

        if (!$apnsKey) {
            return;
        }

        // APNS implementation would go here
        // This would require setting up certificates and JWT tokens
    }
}