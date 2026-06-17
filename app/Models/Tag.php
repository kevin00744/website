<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tag extends Model
{
    protected $fillable = ['name', 'slug'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($tag) {
            if (empty($tag->slug)) {
                $slug = Str::slug($tag->name);
                $tag->slug = $slug ?: 'tag-' . Str::uuid()->toString();
            }
        });
    }

    public function posts()
    {
        return $this->belongsToMany(Post::class, 'post_tag');
    }
}
