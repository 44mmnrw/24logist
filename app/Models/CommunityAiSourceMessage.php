<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityAiSourceMessage extends Model
{
    protected $fillable = [
        'community_ai_source_id', 'external_message_id', 'sender_key', 'sender_name',
        'text', 'content_hash', 'sent_at', 'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'sender_name' => 'encrypted',
            'text' => 'encrypted',
            'raw_payload' => 'encrypted:array',
            'sent_at' => 'datetime',
        ];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(CommunityAiSource::class, 'community_ai_source_id');
    }
}
