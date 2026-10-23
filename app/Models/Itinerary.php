<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Itinerary extends Model
{
    protected $fillable = [
        'user_id', 'exam_centre_id', 'title', 'origin_city', 'origin_lat', 'origin_lng',
        'destination_city', 'departure_at', 'budget', 'preferred_modes',
        'options_snapshot', 'data_status', 'data_limitations', 'notes', 'status',
    ];

    protected function casts(): array
    {
        return [
            'options_snapshot' => 'array',
            'departure_at' => 'datetime',
            'last_viewed_at' => 'datetime',
            'budget' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function centre()
    {
        return $this->belongsTo(ExamCentre::class, 'exam_centre_id');
    }

    public function scopeOwnedBy(Builder $q, int $userId): Builder
    {
        return $q->where('user_id', $userId);
    }

    public function isOwnedBy(?User $user): bool
    {
        return $user !== null && (int) $this->user_id === (int) $user->id;
    }

    /** @return array<int,string> */
    public function modeLabels(): array
    {
        return array_map(
            fn ($m) => config('exam_travel.travel_modes.'.trim($m), ucfirst(trim($m))),
            explode(',', (string) $this->preferred_modes),
        );
    }
}
