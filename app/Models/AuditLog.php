<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Append-only audit trail for sensitive administrative actions.
 *
 * Rows are never updated or deleted by application code, and summaries deliberately
 * exclude passwords, tokens and full personal payloads.
 */
class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'action', 'entity_type', 'entity_id', 'summary', 'ip_address'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function record(string $action, ?string $entityType = null, ?int $entityId = null, ?string $summary = null): self
    {
        return static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'summary' => $summary !== null ? mb_substr($summary, 0, 500) : null,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
        ]);
    }
}
