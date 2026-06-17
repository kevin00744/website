<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Category;
use App\Models\User;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'total_posts'      => Post::count(),
                'published_posts'  => Post::where('status', 'published')->count(),
                'draft_posts'      => Post::where('status', 'draft')->count(),
                'total_categories' => Category::count(),
                'total_users'      => User::count(),
            ],
            'recent_posts' => Post::with('author:id,name')
                ->orderByDesc('created_at')
                ->limit(5)
                ->get(['id', 'title', 'status', 'created_at', 'user_id']),
        ]);
    }
}
