<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $fillable = [
        'user_id', 'filename', 'original_name', 'mime_type',
        'size', 'disk', 'path', 'url', 'thumbnails', 'alt', 'caption',
    ];

    protected function casts(): array
    {
        return ['thumbnails' => 'array'];
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function thumbnail(string $size = 'medium'): ?string
    {
        return $this->thumbnails[$size] ?? $this->url;
    }

    public function humanSize(): string
    {
        $bytes = $this->size;
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }
}
