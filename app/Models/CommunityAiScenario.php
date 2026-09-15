<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunityAiScenario extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_PREPARING = 'preparing';

    public const STATUS_REVIEW = 'pending_review';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_RUNNING = 'running';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Черновик',
        self::STATUS_QUEUED => 'В очереди',
        self::STATUS_PREPARING => 'Анализируется',
        self::STATUS_REVIEW => 'Ждёт проверки',
        self::STATUS_APPROVED => 'Одобрен',
        self::STATUS_SCHEDULED => 'Запланирован',
        self::STATUS_RUNNING => 'Выполняется',
        self::STATUS_PAUSED => 'Приостановлен',
        self::STATUS_COMPLETED => 'Завершён',
        self::STATUS_FAILED => 'Ошибка',
        self::STATUS_CANCELLED => 'Отменён',
    ];

    protected $fillable = [
        'community_category_id', 'source_ids', 'source_from', 'source_to', 'scan_keywords', 'title',
        'editor_brief', 'status', 'planned_at', 'started_at', 'completed_at',
        'last_error', 'settings',
    ];

    protected function casts(): array
    {
        return [
            'source_ids' => 'array',
            'scan_keywords' => 'array',
            'settings' => 'array',
            'source_from' => 'datetime',
            'source_to' => 'datetime',
            'planned_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CommunityCategory::class, 'community_category_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(CommunityAiScenarioStep::class)->orderBy('sequence');
    }

    public function generations(): HasMany
    {
        return $this->hasMany(CommunityAiGeneration::class);
    }
}
