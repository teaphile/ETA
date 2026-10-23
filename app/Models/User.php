<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    public const ROLE_STUDENT = 'student';

    public const ROLE_MODERATOR = 'moderator';

    public const ROLE_ADMIN = 'admin';

    protected $fillable = [
        'name', 'email', 'password', 'role', 'phone', 'current_city', 'state',
        'preferred_language', 'account_status', 'deletion_requested_at',
        'last_login_at', 'terms_accepted_at',
    ];

    /**
     * Default attribute values for a new (not-yet-hydrated) instance.
     *
     * These mirror the database defaults. Without them, a model created in memory
     * (e.g. via a factory) would not carry the columns the views read, which the
     * strict-mode guard correctly flags as a missing attribute.
     */
    protected $attributes = [
        'role' => self::ROLE_STUDENT,
        'account_status' => 'active',
        'preferred_language' => 'en',
        'phone' => null,
        'current_city' => null,
        'state' => null,
        'deletion_requested_at' => null,
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'deletion_requested_at' => 'datetime',
            'last_login_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isModerator(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_MODERATOR], true);
    }

    public function isSuspended(): bool
    {
        return $this->account_status === 'suspended';
    }

    public function hasPendingDeletion(): bool
    {
        return $this->deletion_requested_at !== null;
    }

    public function canModerateContent(): bool
    {
        return $this->isModerator() && ! $this->isSuspended();
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim((string) $this->name)) ?: [];

        return mb_strtoupper(collect($parts)->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode(''));
    }

    public function itineraries(): HasMany
    {
        return $this->hasMany(Itinerary::class);
    }

    public function communityPosts(): HasMany
    {
        return $this->hasMany(CommunityPost::class);
    }
}
