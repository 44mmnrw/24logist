<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
