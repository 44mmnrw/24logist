<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingLead extends Model
{
    public const TYPE_QUIZ = 'quiz';

    public const TYPE_CONTACT = 'contact';

    public const STATUS_NEW = 'new';

    public const STATUS_PROCESSED = 'processed';

    /** @var array<string, string> */
    public const TYPE_LABELS = [
        self::TYPE_QUIZ => 'Квиз (просчёт тарифа)',
        self::TYPE_CONTACT => 'Форма контактов',
    ];

    /** @var array<string, string> */
    public const STATUS_LABELS = [
        self::STATUS_NEW => 'Новая',
        self::STATUS_PROCESSED => 'Обработана',
    ];

    protected $fillable = [
        'type',
        'status',
        'name',
        'phone',
        'email',
        'message',
        'quiz_answers',
        'source_url',
        'ip',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'quiz_answers' => 'array',
        ];
    }

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }
}
