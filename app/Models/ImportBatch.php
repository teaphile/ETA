<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportBatch extends Model
{
    protected $fillable = [
        'user_id', 'file_name', 'status', 'total_rows', 'valid_rows',
        'error_rows', 'duplicate_rows', 'imported_rows', 'updated_rows', 'notes',
    ];

    public const STATUS_PENDING = 'pending';

    public const STATUS_AWAITING_APPROVAL = 'awaiting_approval';

    public const STATUS_IMPORTED = 'imported';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_FAILED = 'failed';

    public function rows()
    {
        return $this->hasMany(ImportRow::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isAwaitingApproval(): bool
    {
        return $this->status === self::STATUS_AWAITING_APPROVAL;
    }

    public function summary(): string
    {
        return sprintf(
            '%d valid · %d duplicate · %d error · %d imported',
            $this->valid_rows, $this->duplicate_rows, $this->error_rows, $this->imported_rows,
        );
    }
}
