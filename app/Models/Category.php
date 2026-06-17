<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Category extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'parent_id',
        'seo_title', 'seo_description', 'sort_order',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($cat) {
            if (empty($cat->slug)) {
                $slug = Str::slug($cat->name);
                // 中文名稱 slug 會是空字串，改用拼音化處理
                $cat->slug = $slug ?: 'cat-' . Str::uuid()->toString();
            }
        });
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}
