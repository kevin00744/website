<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Support\SiteCss;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $posts = Post::with(['author:id,name', 'category:id,name'])
            ->when($request->search, fn($q) => $q->where('title', 'like', "%{$request->search}%"))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Posts/Index', [
            'posts'   => $posts,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Posts/Edit', [
            'post'       => null,
            'categories' => Category::select('id', 'name')->get(),
            'tags'       => Tag::select('id', 'name')->get(),
            'css'        => SiteCss::for(null),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'           => 'required|string|max:255',
            'slug'            => 'nullable|string|max:255|alpha_dash|unique:posts,slug',
            'type'            => 'required|in:post,page',
            'content'         => 'required|string',
            'excerpt'         => 'nullable|string',
            'status'          => 'required|in:draft,review,published,archived',
            'category_id'     => 'nullable|exists:categories,id',
            'tags'            => 'nullable|array',
            'seo_title'       => 'nullable|string|max:255',
            'seo_description' => 'nullable|string',
            'published_at'    => 'nullable|date',
            'nav_order'       => 'nullable|integer',
            'nav_label'       => 'nullable|string|max:255',
        ]);

        $slug = $data['slug'] ?: Str::slug($data['title']);
        $post = Post::create([
            ...$data,
            'user_id'      => auth()->id(),
            'slug'         => $slug ?: (string) Str::uuid(),
            'published_at' => $data['published_at'] ?? ($data['status'] === 'published' ? now() : null),
        ]);

        if (!empty($data['tags'])) {
            $post->tags()->sync($data['tags']);
        }

        return redirect()->route('admin.posts.index')->with('success', '文章已建立。');
    }

    public function edit(Post $post)
    {
        return Inertia::render('Admin/Posts/Edit', [
            'post'       => $post->load('tags:id,name'),
            'categories' => Category::select('id', 'name')->get(),
            'tags'       => Tag::select('id', 'name')->get(),
            'css'        => SiteCss::for($post->slug),
        ]);
    }

    public function update(Request $request, Post $post)
    {
        $data = $request->validate([
            'title'           => 'required|string|max:255',
            'slug'            => 'nullable|string|max:255|alpha_dash|unique:posts,slug,' . $post->id,
            'type'            => 'required|in:post,page',
            'content'         => 'required|string',
            'excerpt'         => 'nullable|string',
            'status'          => 'required|in:draft,review,published,archived',
            'category_id'     => 'nullable|exists:categories,id',
            'tags'            => 'nullable|array',
            'seo_title'       => 'nullable|string|max:255',
            'seo_description' => 'nullable|string',
            'published_at'    => 'nullable|date',
            'nav_order'       => 'nullable|integer',
            'nav_label'       => 'nullable|string|max:255',
        ]);

        if (empty($data['slug'])) {
            $data['slug'] = $post->slug;
        }

        $post->update([
            ...$data,
            'published_at' => $data['published_at']
                ?? ($data['status'] === 'published' && !$post->published_at ? now() : $post->published_at),
        ]);

        if (isset($data['tags'])) {
            $post->tags()->sync($data['tags']);
        }

        return redirect()->route('admin.posts.index')->with('success', '文章已更新。');
    }

    public function destroy(Post $post)
    {
        $post->delete();
        return back()->with('success', '文章已刪除。');
    }
}
