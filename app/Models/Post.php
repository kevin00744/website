<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'category_id', 'featured_image_id',
        'title', 'slug', 'nav_label', 'nav_order', 'excerpt', 'content', 'content_json',
        'status', 'type', 'is_featured', 'allow_comments',
        'seo_title', 'seo_description', 'og_image',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'content_json' => 'array',
            'is_featured'  => 'boolean',
            'allow_comments' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    // Auto-generate slug
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($post) {
            if (empty($post->slug)) {
                $post->slug = Str::slug($post->title);
            }
        });
    }

    // Relationships
    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'post_tag');
    }

    public function featuredImage()
    {
        return $this->belongsTo(Media::class, 'featured_image_id');
    }

    public function revisions()
    {
        return $this->hasMany(PostRevision::class)->latest();
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
                     ->where('published_at', '<=', now());
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // Helpers
    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function incrementViews(): void
    {
        $this->increment('views');
    }
}
