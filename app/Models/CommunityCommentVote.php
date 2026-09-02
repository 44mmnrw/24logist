<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommunityCommentVote extends Model
{
    protected $fillable = ['community_user_id', 'community_comment_id', 'value'];
}
