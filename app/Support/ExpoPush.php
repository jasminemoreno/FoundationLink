<?php

namespace App\Support;

use App\Models\Notification;
use App\Models\PushToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sends in-app notifications to the donor's phone(s) as push notifications
 * through Expo (https://docs.expo.dev/push-notifications/sending-notifications/).
 *
 * Notifications created during a request are collected and sent together
 * once the response is done (AppServiceProvider calls flush() when the app
 * terminates), in groups of 100, Expo's limit. Sending must never break the
 * action that created the notification: every error is only logged.
 */
class ExpoPush
{
    private const BATCH_SIZE = 100;

    /** @var int[] ids of notifications waiting to be pushed */
    private array $pending = [];

    public function queue(Notification $notification): void
    {
        if (config('services.expo.push_enabled')) {
            $this->pending[] = $notification->id;
        }
    }

    public function flush(): void
    {
        if (!$this->pending) {
            return;
        }
        $ids = $this->pending;
        $this->pending = [];

        try {
            $this->send($ids);
        } catch (Throwable $e) {
            Log::warning('Push notifications not sent: ' . $e->getMessage());
        }
    }

    private function send(array $notificationIds): void
    {
        $notifications = Notification::whereIn('id', $notificationIds)->get();
        $tokens = PushToken::whereIn('user_id', $notifications->pluck('user_id')->unique())
            ->get()
            ->groupBy('user_id');

        $messages = [];
        foreach ($notifications as $n) {
            foreach ($tokens->get($n->user_id, []) as $token) {
                $messages[] = [
                    'to' => $token->token,
                    'title' => $n->title,
                    'body' => (string) $n->message,
                    'data' => [
                        'notification_id' => $n->id,
                        'type' => $n->type,
                        'link' => $n->donorLink(),
                    ],
                    'sound' => 'default',
                    'channelId' => 'default', // created by the app on Android
                    'priority' => 'high',
                ];
            }
        }

        foreach (array_chunk($messages, self::BATCH_SIZE) as $batch) {
            $this->sendBatch($batch);
        }
    }

    private function sendBatch(array $batch): void
    {
        try {
            $request = Http::acceptJson()->asJson()->connectTimeout(3)->timeout(5);
            if ($accessToken = config('services.expo.access_token')) {
                $request = $request->withToken($accessToken);
            }
            $response = $request->post(config('services.expo.push_url'), $batch);
        } catch (Throwable $e) {
            Log::warning('Expo push request failed: ' . $e->getMessage());
            return;
        }

        if ($response->failed()) {
            Log::warning('Expo push request refused', ['status' => $response->status(), 'body' => $response->json('errors')]);
            return;
        }

        // Tickets come back in the same order as the messages. A phone that
        // uninstalled the app answers DeviceNotRegistered: stop sending to it.
        foreach ((array) $response->json('data') as $i => $ticket) {
            if (($ticket['details']['error'] ?? null) === 'DeviceNotRegistered' && isset($batch[$i])) {
                PushToken::where('token', $batch[$i]['to'])->delete();
            }
        }
    }
}
