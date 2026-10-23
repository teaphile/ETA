<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Examination extends Model
{
    protected $fillable = [
        'name', 'slug', 'conducting_authority', 'official_url', 'description', 'status',
    ];

    public function centres()
    {
        return $this->hasMany(ExamCentre::class);
    }

    public function sessions()
    {
        return $this->hasMany(ExamSession::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', 'active');
    }
}
