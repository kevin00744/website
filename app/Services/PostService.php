<?php

namespace App\Services;

use App\Models\Post;
use App\Models\PostRevision;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PostService
{
    public function create(array $data, User $author): Post
    {
        $data['user_id'] = $author->id;
        $data['slug'] = $this->uniqueSlug($data['title'], $data['slug'] ?? null);

        if ($data['status'] === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        $post = Post::create($data);

        if (!empty($data['tags'])) {
            $post->tags()->sync($data['tags']);
        }

        $this->saveRevision($post, $author);
        $this->clearCache();

        return $post->load(['author', 'category', 'tags', 'featuredImage']);
    }

    public function update(Post $post, array $data, User $editor): Post
    {
        if (isset($data['title']) && empty($data['slug'])) {
            $data['slug'] = $this->uniqueSlug($data['title'], null, $post->id);
        }

        if (($data['status'] ?? '') === 'published' && !$post->published_at) {
            $data['published_at'] = now();
        }

        $post->update($data);

        if (isset($data['tags'])) {
            $post->tags()->sync($data['tags']);
        }

        $this->saveRevision($post, $editor);
        $this->clearCache($post->slug);

        return $post->refresh()->load(['author', 'category', 'tags', 'featuredImage']);
    }

    public function delete(Post $post): void
    {
        $this->clearCache($post->slug);
        $post->delete();
    }

    private function saveRevision(Post $post, User $user): void
    {
        PostRevision::create([
            'post_id'      => $post->id,
            'user_id'      => $user->id,
            'title'        => $post->title,
            'content'      => $post->content,
            'content_json' => $post->content_json,
        ]);

        // Keep only last 20 revisions
        $post->revisions()->skip(20)->each->delete();
    }

    private function uniqueSlug(string $title, ?string $slug, ?int $exceptId = null): string
    {
        $base = Str::slug($slug ?? $title);
        $candidate = $base;
        $i = 1;

        while (
            Post::where('slug', $candidate)
                ->when($exceptId, fn($q) => $q->where('id', '!=', $exceptId))
                ->exists()
        ) {
            $candidate = $base . '-' . $i++;
        }

        return $candidate;
    }

    private function clearCache(?string $slug = null): void
    {
        Cache::forget('posts.recent');
        Cache::forget('posts.featured');
        if ($slug) {
            Cache::forget("post.{$slug}");
        }
    }
}
