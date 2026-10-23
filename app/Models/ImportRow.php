<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportRow extends Model
{
    protected $fillable = ['import_batch_id', 'row_number', 'data', 'status', 'error_message'];

    protected function casts(): array
    {
        return ['data' => 'array'];
    }

    public function batch()
    {
        return $this->belongsTo(ImportBatch::class, 'import_batch_id');
    }

    public function hasErrors(): bool
    {
        return $this->status === 'error';
    }
}
