<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunityAiScenarioStep extends Model
{
    protected $fillable = [
        'community_ai_scenario_id', 'community_ai_persona_id', 'parent_step_id',
        'type', 'sequence', 'planned_delay_minutes', 'purpose', 'conversation_move', 'target_word_count', 'draft_title',
        'draft_body', 'status', 'community_post_id', 'community_comment_id',
        'generated_at', 'scheduled_at', 'published_at', 'last_error', 'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'planned_delay_minutes' => 'integer',
            'target_word_count' => 'integer',
            'generated_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function scenario(): BelongsTo
    {
        return $this->belongsTo(CommunityAiScenario::class, 'community_ai_scenario_id');
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(CommunityAiPersona::class, 'community_ai_persona_id');
    }

    public function parentStep(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_step_id');
    }

    public function generations(): HasMany
    {
        return $this->hasMany(CommunityAiGeneration::class);
    }
}
