<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityModerationAction extends Model
{
    public $timestamps = true;

    const UPDATED_AT = null;

    protected $fillable = [
        'community_user_id', 'admin_user_id', 'target_type', 'target_id',
        'action', 'reason', 'metadata',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    public function communityUser(): BelongsTo
    {
        return $this->belongsTo(CommunityUser::class, 'community_user_id')->withTrashed();
    }
}
