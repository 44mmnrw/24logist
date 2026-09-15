<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunityAiPersona extends Model
{
    protected $fillable = [
        'community_user_id',
        'slug',
        'role_description',
        'personality_description',
        'provider',
        'provider_agent_id',
        'provider_base_url',
        'model',
        'prompt_version',
        'system_prompt',
        'is_active',
        'can_create_posts',
        'can_create_comments',
        'requires_review',
        'daily_post_limit',
        'daily_comment_limit',
        'max_post_tokens',
        'max_comment_tokens',
        'settings',
        'last_acted_at',
    ];

    protected function casts(): array
    {
        return [
            'prompt_version' => 'integer',
            'is_active' => 'boolean',
            'can_create_posts' => 'boolean',
            'can_create_comments' => 'boolean',
            'requires_review' => 'boolean',
            'daily_post_limit' => 'integer',
            'daily_comment_limit' => 'integer',
            'max_post_tokens' => 'integer',
            'max_comment_tokens' => 'integer',
            'settings' => 'array',
            'last_acted_at' => 'datetime',
        ];
    }

    public function communityUser(): BelongsTo
    {
        return $this->belongsTo(CommunityUser::class);
    }

    public function scenarioSteps(): HasMany
    {
        return $this->hasMany(CommunityAiScenarioStep::class);
    }
}
