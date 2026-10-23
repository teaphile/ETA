<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Optional, controlled-retention chat transcript.
 *
 * Only created for signed-in users. A scheduled command (`assistant:prune-chats`)
 * deletes transcripts older than the configured retention window.
 */
class ChatConversation extends Model
{
    protected $fillable = ['user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function scopeOlderThan($q, int $days)
    {
        return $q->where('updated_at', '<', now()->subDays($days));
    }
}
