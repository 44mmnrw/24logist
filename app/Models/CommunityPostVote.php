<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommunityPostVote extends Model
{
    protected $fillable = ['community_user_id', 'community_post_id', 'value'];
}
