<?php

namespace App\Models;

use App\Integrations\Support\DataStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamCentre extends Model
{
    use HasFactory;

    protected $fillable = [
        'examination_id', 'exam_session_id', 'centre_code', 'name', 'address',
        'city', 'district', 'state', 'pin_code', 'latitude', 'longitude',
        'verification_status', 'data_status', 'source_name', 'source_url',
        'source_type', 'provider_reference', 'error_or_limitation',
        'last_verified_at', 'valid_from', 'valid_until', 'retrieved_at',
        'is_demo', 'status', 'created_by', 'import_batch_id',
    ];

    protected function casts(): array
    {
        return [
            'last_verified_at' => 'datetime',
            'retrieved_at' => 'datetime',
            'valid_from' => 'date',
            'valid_until' => 'date',
            'is_demo' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function examination()
    {
        return $this->belongsTo(Examination::class);
    }

    public function session()
    {
        return $this->belongsTo(ExamSession::class, 'exam_session_id');
    }

    public function localServices()
    {
        return $this->hasMany(LocalService::class);
    }

    public function importBatch()
    {
        return $this->belongsTo(ImportBatch::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', 'active');
    }

    public function scopeWithReliabilityData(Builder $q): Builder
    {
        return $q->with(['examination:id,name', 'session:id,name']);
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /**
     * A centre is stale when nothing has re-verified it inside the freshness window.
     * "Unavailable" and "not yet checked" are never presented as fresh.
     */
    public function isStale(): bool
    {
        if (! DataStatus::isVerified($this->data_status)) {
            return true;
        }

        if ($this->valid_until && $this->valid_until->isPast()) {
            return true;
        }

        $window = (int) config('exam_travel.freshness.centre_months', 6);

        return ! $this->last_verified_at || $this->last_verified_at->lt(now()->subMonths($window));
    }

    public function statusLabel(): string
    {
        if ($this->isStale() && DataStatus::isVerified($this->data_status)) {
            return DataStatus::label(DataStatus::Stale->value);
        }

        return DataStatus::label($this->data_status);
    }

    public function mapsDirectionsUrl(?string $origin = null): string
    {
        $base = 'https://www.google.com/maps/dir/?api=1';

        if ($origin) {
            $base .= '&origin='.urlencode($origin);
        }

        return $this->hasCoordinates()
            ? $base.'&destination='.$this->latitude.','.$this->longitude
            : $base.'&destination='.urlencode(trim($this->name.', '.$this->address.', '.$this->city.', '.$this->state));
    }

    public function fullAddress(): string
    {
        return collect([$this->address, $this->district, $this->city, $this->state, $this->pin_code])
            ->filter(fn ($v) => filled($v))
            ->implode(', ');
    }
}
