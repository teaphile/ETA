<?php

namespace App\Contracts;

use App\Models\User;

/**
 * Outbound notification delivery (email now, SMS/push later).
 *
 * The default implementation uses Laravel's mail transport; a "log" provider keeps
 * local development working with zero credentials. Notification failures must never
 * break the user's primary action.
 */
interface NotificationProvider
{
    public function name(): string;

    public function isAvailable(): bool;

    /**
     * @param  array<string,mixed>  $data  Template variables (never credentials, tokens or passwords).
     */
    public function send(User $user, string $template, array $data = []): bool;
}
