<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Support\SiteCss;
use Inertia\Inertia;

class SiteController extends Controller
{
    private const CONTACT_LABEL = '聯絡璨妍';

    public function home()
    {
        return $this->render('home');
    }

    public function page(string $slug)
    {
        return $this->render($slug);
    }

    private function render(string $slug)
    {
        $post = Post::where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        return Inertia::render('Site/Page', [
            'post'    => $post,
            'nav'     => $this->nav(),
            'contact' => $this->contactLink(),
            'css'     => SiteCss::for($slug),
        ]);
    }

    // 任何 type=page、已發布、且設定了 nav_order 的文章都會自動出現在導覽列，
    // 依 nav_order 排序；顯示文字優先用 nav_label，沒填就用 title。
    private function nav()
    {
        return Post::where('type', 'page')
            ->where('status', 'published')
            ->whereNotNull('nav_order')
            ->orderBy('nav_order')
            ->get(['title', 'slug', 'nav_label'])
            ->map(fn ($post) => [
                'slug'  => $post->slug,
                'title' => $post->nav_label ?: $post->title,
            ]);
    }

    private function contactLink()
    {
        $exists = Post::where('slug', 'contact')->where('status', 'published')->exists();

        return $exists ? ['slug' => 'contact', 'title' => self::CONTACT_LABEL] : null;
    }
}
