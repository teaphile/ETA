<?php

namespace App\Integrations\Notifications;

use App\Contracts\NotificationProvider;
use App\Mail\PlatformMail;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Sends notifications through Laravel's configured mail transport.
 *
 * Notification failure is logged and swallowed: a student's primary action must never
 * fail because email delivery did. Configure MAIL_* in the environment to activate.
 */
class MailNotificationProvider implements NotificationProvider
{
    public function name(): string
    {
        return 'mail';
    }

    public function isAvailable(): bool
    {
        return ! in_array(config('mail.default'), ['log', 'array', null], true)
            && filled(config('mail.from.address'));
    }

    public function send(User $user, string $template, array $data = []): bool
    {
        try {
            Mail::to($user->email, $user->name)->send(new PlatformMail($template, $data));

            return true;
        } catch (\Throwable $e) {
            Log::warning('Notification delivery failed', [
                'template' => $template,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
