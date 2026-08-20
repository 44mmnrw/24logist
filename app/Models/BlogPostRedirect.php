<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlogPostRedirect extends Model
{
    protected $fillable = [
        'blog_post_id',
        'slug',
    ];

    /**
     * @return BelongsTo<BlogPost, BlogPostRedirect>
     */
    public function blogPost(): BelongsTo
    {
        return $this->belongsTo(BlogPost::class);
    }
}
