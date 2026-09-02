<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
