<?php

namespace App\Models;

use Database\Factories\CommunityUserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class CommunityUser extends Authenticatable
{
    /** @use HasFactory<CommunityUserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'username', 'role', 'karma', 'onboarded_at', 'terms_accepted_at',
        'suspended_until', 'banned_at',
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
}
