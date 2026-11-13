<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlogPost extends Model
{
    protected $table = 'blog_posts';
    
    protected $fillable = [
        'title', 'slug', 'excerpt', 'content', 'image',
        'og_image', 'canonical_url', 'schema_type', 'schema_json',
        'category', 'author', 'status', 'views',
        'meta_title', 'meta_description', 'meta_keywords',
        'focus_keyword', 'robots_meta', 'is_cornerstone',
    ];
    
    protected $casts = [
        'views' => 'integer',
        'is_cornerstone' => 'boolean',
    ];
    
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Posts that have not been consolidated into another canonical URL.
     */
    public function scopeIndexable($query)
    {
        $excludedSlugs = array_values(array_unique(array_merge(
            array_keys(config('seo.blog_redirects', [])),
            config('seo.blog_gone', [])
        )));

        return empty($excludedSlugs)
            ? $query
            : $query->whereNotIn('slug', $excludedSlugs);
    }
}
