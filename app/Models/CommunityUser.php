<?php

namespace App\Models;

use Database\Factories\CommunityUserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class CommunityUser extends Authenticatable
{
    /** @use HasFactory<CommunityUserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'username', 'display_name', 'avatar_path', 'avatar_source', 'transport_role', 'bio', 'role', 'karma', 'onboarded_at', 'terms_accepted_at',
        'suspended_until', 'banned_at',
    ];

    public const TRANSPORT_ROLES = [
        'carrier' => 'Перевозчик',
        'freight_forwarder' => 'Экспедитор',
        'cargo_owner' => 'Грузовладелец',
        'driver' => 'Водитель',
        'logistician' => 'Логист',
        'dispatcher' => 'Диспетчер',
        'other' => 'Другое',
    ];

    protected $hidden = ['remember_token'];

    protected function casts(): array
    {
        return [
            'onboarded_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'suspended_until' => 'datetime',
            'banned_at' => 'datetime',
        ];
    }

    public function identities(): HasMany
    {
        return $this->hasMany(CommunityIdentity::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(CommunityPost::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(CommunityComment::class);
    }

    public function communityNotifications(): HasMany
    {
        return $this->hasMany(CommunityNotification::class);
    }

    public function isOnboarded(): bool
    {
        return $this->onboarded_at !== null && $this->terms_accepted_at !== null;
    }

    public function isModerator(): bool
    {
        return $this->role === 'moderator';
    }

    public function isRestricted(): bool
    {
        return $this->banned_at !== null
            || ($this->suspended_until !== null && $this->suspended_until->isFuture());
    }

    public function getRouteKeyName(): string
    {
        return 'username';
    }

    public function avatarUrl(): ?string
    {
        return filled($this->avatar_path)
            ? Storage::disk('public')->url((string) $this->avatar_path)
            : null;
    }

    public function avatarInitial(): string
    {
        return mb_strtoupper(mb_substr($this->displayName(), 0, 1));
    }

    public function displayName(): string
    {
        return filled($this->display_name) ? (string) $this->display_name : (string) $this->username;
    }

    public function transportRoleLabel(): ?string
    {
        return self::TRANSPORT_ROLES[$this->transport_role] ?? null;
    }
}
