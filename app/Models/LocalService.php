<?php

namespace App\Models;

use App\Integrations\Support\DataStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LocalService extends Model
{
    protected $fillable = [
        'exam_centre_id', 'name', 'category', 'city', 'address', 'latitude', 'longitude',
        'phone', 'website', 'opening_hours', 'rating', 'reviews_count', 'price_from',
        'source', 'source_name', 'source_type', 'source_url', 'provider_reference',
        'data_status', 'error_or_limitation', 'is_demo', 'last_verified_at',
        'valid_from', 'valid_until', 'retrieved_at', 'status',
    ];

    protected function casts(): array
    {
        return [
            'is_demo' => 'boolean',
            'last_verified_at' => 'datetime',
            'retrieved_at' => 'datetime',
            'valid_from' => 'date',
            'valid_until' => 'date',
            'rating' => 'float',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function centre()
    {
        return $this->belongsTo(ExamCentre::class, 'exam_centre_id');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', 'active');
    }

    /** Freshness is about the *source*, so unverified rows are never called fresh. */
    public function isStale(): bool
    {
        if (! DataStatus::isVerified($this->data_status) && ! $this->is_demo) {
            return true;
        }

        $window = (int) config('exam_travel.freshness.service_months', 6);

        return ! $this->last_verified_at || $this->last_verified_at->lt(now()->subMonths($window));
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function categoryLabel(): string
    {
        return config('exam_travel.service_categories.'.$this->category, ucfirst(str_replace('_', ' ', (string) $this->category)));
    }
}
