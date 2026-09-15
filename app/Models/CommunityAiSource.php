<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunityAiSource extends Model
{
    protected $fillable = [
        'platform', 'name', 'external_chat_id', 'public_url', 'is_active',
        'last_synced_at', 'last_error', 'settings',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_synced_at' => 'datetime',
            'settings' => 'array',
        ];
    }

    public function messages(): HasMany
    {
        return $this->hasMany(CommunityAiSourceMessage::class);
    }
}
