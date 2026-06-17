<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * 從 importer/tsanyen.json 匯入 tsanyen.com 的靜態頁面內容，
 * 取代 import:static-wordpress（該指令是針對文章設計，且 slug 推導邏輯
 * 對 index.html 等頂層頁面不適用）。
 */
class ImportSitePages extends Command
{
    protected $signature = 'import:site-pages {--json=importer/tsanyen.json}';

    protected $description = '從 tsanyen.json 匯入網站靜態頁面（about/face/hair/tech/contact/home）';

    // 原始匯出檔案裡 slug=post 對應首頁 index.html
    private const SLUG_MAP = [
        'post' => 'home',
    ];

    // 排除非真實頁面的雜訊
    private const SKIP_SLUGS = ['hello-world'];

    // 主導覽列順序與顯示文字（對應原網站選單，與頁面標題不同）
    private const NAV = [
        'home'  => ['order' => 1, 'label' => '首頁'],
        'about' => ['order' => 2, 'label' => '關於璨妍'],
        'tech'  => ['order' => 3, 'label' => '核心科技'],
        'hair'  => ['order' => 4, 'label' => '璨妍護髮原液'],
        'face'  => ['order' => 5, 'label' => '璨妍美容原液'],
    ];

    public function handle(): int
    {
        $path = base_path($this->option('json'));
        if (!File::exists($path)) {
            $this->error("找不到檔案：{$path}");
            return Command::FAILURE;
        }

        $data = json_decode(File::get($path), true);
        $authorId = User::first()->id;

        foreach ($data['posts'] as $page) {
            $slug = self::SLUG_MAP[$page['slug']] ?? $page['slug'];

            if (in_array($page['slug'], self::SKIP_SLUGS)) {
                $this->line("略過: {$page['slug']}");
                continue;
            }

            $content = $this->fixDoubleEncodedAssetPaths($page['content']);

            Post::updateOrCreate(
                ['slug' => $slug],
                [
                    'user_id'         => $authorId,
                    'title'           => $page['title'],
                    'nav_order'       => self::NAV[$slug]['order'] ?? null,
                    'nav_label'       => self::NAV[$slug]['label'] ?? null,
                    'excerpt'         => $page['excerpt'],
                    'content'         => $content,
                    'status'          => 'published',
                    'type'            => 'page',
                    'seo_title'       => $page['seo_title'] ?: null,
                    'seo_description' => $page['seo_description'] ?: null,
                    'published_at'    => now(),
                ]
            );

            $this->info("匯入: {$slug} ({$page['title']})");
        }

        return Command::SUCCESS;
    }

    /**
     * wget 匯出的 HTML 裡，中文檔名路徑被多重 URL 編碼過
     * （例如 %25E5%259C%2596... 而非 %E5%9C%96...），
     * 導致瀏覽器實際請求的路徑跟磁碟上的檔名解碼後不一致。
     * 這裡把 wp-content/ 開頭的路徑多 decode 一次並補上開頭斜線。
     */
    private function fixDoubleEncodedAssetPaths(string $html): string
    {
        return preg_replace_callback(
            '/(src|srcset)="([^"]*wp-content[^"]*)"/i',
            function ($m) {
                $attr  = $m[1];
                $value = $m[2];

                if ($attr === 'srcset') {
                    $parts = array_map(function ($entry) {
                        $entry = trim($entry);
                        return preg_replace_callback('/^(\S*wp-content\S*)(\s+\S+)?$/', function ($p) {
                            return $this->normalizePath($p[1]) . ($p[2] ?? '');
                        }, $entry);
                    }, explode(',', $value));
                    $value = implode(', ', $parts);
                } else {
                    $value = $this->normalizePath($value);
                }

                return "{$attr}=\"{$value}\"";
            },
            $html
        );
    }

    private function normalizePath(string $path): string
    {
        $path = rawurldecode($path);
        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }
        return $path;
    }
}
