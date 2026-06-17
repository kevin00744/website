<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;

class PostApiController extends Controller
{
    public function index(Request $request)
    {
        $posts = Post::published()
            ->ofType('post')
            ->with(['author:id,name,avatar', 'category:id,name,slug', 'tags:id,name,slug', 'featuredImage'])
            ->when($request->category, fn($q) =>
                $q->whereHas('category', fn($q) => $q->where('slug', $request->category)))
            ->when($request->tag, fn($q) =>
                $q->whereHas('tags', fn($q) => $q->where('slug', $request->tag)))
            ->when($request->search, fn($q) =>
                $q->where('title', 'like', "%{$request->search}%"))
            ->select(['id','title','slug','excerpt','status','is_featured','views','published_at',
                      'user_id','category_id','featured_image_id','seo_title','seo_description'])
            ->orderByDesc('published_at')
            ->paginate($request->per_page ?? 10);

        return response()->json($posts);
    }

    public function show(string $slug)
    {
        $post = Post::published()
            ->where('slug', $slug)
            ->with(['author:id,name,avatar,bio', 'category', 'tags', 'featuredImage'])
            ->firstOrFail();

        $post->incrementViews();

        return response()->json($post);
    }
}
