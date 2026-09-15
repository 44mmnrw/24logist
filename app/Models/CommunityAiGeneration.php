<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityAiGeneration extends Model
{
    protected $fillable = [
        'community_ai_scenario_id', 'community_ai_scenario_step_id',
        'community_ai_persona_id', 'purpose', 'status', 'request_messages',
        'response_payload', 'prompt_tokens', 'completion_tokens', 'last_error',
    ];

    protected function casts(): array
    {
        return [
            'request_messages' => 'encrypted:array',
            'response_payload' => 'encrypted:array',
            'prompt_tokens' => 'integer',
            'completion_tokens' => 'integer',
        ];
    }

    public function scenario(): BelongsTo
    {
        return $this->belongsTo(CommunityAiScenario::class, 'community_ai_scenario_id');
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(CommunityAiScenarioStep::class, 'community_ai_scenario_step_id');
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(CommunityAiPersona::class, 'community_ai_persona_id');
    }
}
