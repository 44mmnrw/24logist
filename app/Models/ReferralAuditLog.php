<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralAuditLog extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['before' => 'array', 'after' => 'array', 'created_at' => 'datetime'];
    }
}
