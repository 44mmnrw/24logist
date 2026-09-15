<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityReport extends Model
{
    protected $fillable = [
        'community_user_id', 'target_type', 'target_id', 'reason', 'details',
        'status', 'resolved_at',
    ];

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(CommunityUser::class, 'community_user_id')->withTrashed();
    }

    public function targetLabel(): string
    {
        return match ($this->target_type) {
            'post' => 'Тема',
            'comment' => 'Комментарий',
            default => 'Материал',
        };
    }

    public function reasonLabel(): string
    {
        return match ($this->reason) {
            'spam' => 'Спам или реклама',
            'abuse' => 'Оскорбления',
            'illegal' => 'Незаконный материал',
            'personal_data' => 'Персональные данные',
            'other' => 'Другое',
            default => 'Не указана',
        };
    }
}
