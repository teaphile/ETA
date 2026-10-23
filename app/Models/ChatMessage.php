<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    protected $fillable = [
        'chat_conversation_id', 'role', 'provider', 'category',
        'content', 'sources', 'limitation', 'reported_at', 'report_reason',
    ];

    protected function casts(): array
    {
        return [
            'sources' => 'array',
            'reported_at' => 'datetime',
        ];
    }

    public function conversation()
    {
        return $this->belongsTo(ChatConversation::class, 'chat_conversation_id');
    }

    public function scopeReported(Builder $q): Builder
    {
        return $q->whereNotNull('reported_at');
    }
}
