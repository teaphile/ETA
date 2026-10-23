<?php

namespace App\Integrations\Notifications;

use App\Contracts\NotificationProvider;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Development/test notification sink.
 *
 * Writes the notification to the application log instead of sending it, so local
 * development and automated tests never require SMTP credentials. Never logs
 * passwords, tokens or personal content beyond the recipient id.
 */
class LogNotificationProvider implements NotificationProvider
{
    public function name(): string
    {
        return 'log';
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function send(User $user, string $template, array $data = []): bool
    {
        Log::info('Notification (log provider)', [
            'template' => $template,
            'user_id' => $user->id,
            'keys' => array_keys($data),
        ]);

        return true;
    }
}
