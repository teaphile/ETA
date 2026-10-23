<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ExamSession extends Model
{
    protected $fillable = ['examination_id', 'name', 'year', 'exam_date', 'status'];

    protected function casts(): array
    {
        return ['exam_date' => 'date'];
    }

    public function examination()
    {
        return $this->belongsTo(Examination::class);
    }

    public function centres()
    {
        return $this->hasMany(ExamCentre::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->whereIn('status', ['upcoming', 'active']);
    }
}
