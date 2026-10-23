<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CommunityPost extends Model
{
    protected $fillable = [
        'user_id', 'examination_id', 'title', 'body', 'city', 'category',
        'status', 'is_locked', 'reports_count', 'last_reply_at',
    ];

    protected function casts(): array
    {
        return [
            'is_locked' => 'boolean',
            'last_reply_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function examination()
    {
        return $this->belongsTo(Examination::class);
    }

    public function replies()
    {
        return $this->hasMany(CommunityReply::class);
    }

    public function scopeVisible(Builder $q): Builder
    {
        return $q->where('status', 'visible');
    }

    public function isVisible(): bool
    {
        return $this->status === 'visible';
    }

    public function canReceiveReplies(): bool
    {
        return $this->isVisible() && ! $this->is_locked;
    }
}
