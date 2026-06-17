<?php
/**
 * WordPress → Laravel CMS 遷移腳本
 *
 * 執行方式：
 *   php artisan migrate:wordpress --wp-host=127.0.0.1 --wp-db=wordpress
 *                                 --wp-user=root --wp-password=secret
 *                                 --wp-prefix=wp_
 *
 * 遷移項目：
 *   - 文章 (posts, pages)
 *   - 分類 (categories)
 *   - 標籤 (tags)
 *   - 用戶 (users)
 *   - 媒體附件路徑記錄
 */

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MigrateWordPress extends Command
{
    protected $signature = 'migrate:wordpress
        {--wp-host=127.0.0.1  : WordPress 資料庫主機}
        {--wp-db=wordpress     : WordPress 資料庫名稱}
        {--wp-user=root        : 資料庫帳號}
        {--wp-password=        : 資料庫密碼}
        {--wp-prefix=wp_       : 資料表前綴}
        {--dry-run             : 模擬執行，不寫入資料}';

    protected $description = '從 WordPress 資料庫遷移內容到 Laravel CMS';

    private \PDO $wp;
    private string $prefix;
    private bool $dryRun;

    // ID mapping (WP id → new id)
    private array $categoryMap = [];
    private array $tagMap      = [];
    private array $userMap     = [];

    public function handle(): int
    {
        $this->dryRun = $this->option('dry-run');
        $this->prefix = $this->option('wp-prefix');

        $this->connectWordPress();

        $this->info('開始遷移 WordPress 資料...');
        if ($this->dryRun) {
            $this->warn('DRY RUN 模式：不會寫入任何資料');
        }

        DB::transaction(function () {
            $this->migrateUsers();
            $this->migrateCategories();
            $this->migrateTags();
            $this->migratePosts();
        });

        $this->info('遷移完成！');
        return Command::SUCCESS;
    }

    private function connectWordPress(): void
    {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            $this->option('wp-host'),
            $this->option('wp-db')
        );

        $this->wp = new \PDO($dsn, $this->option('wp-user'), $this->option('wp-password'));
        $this->wp->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_OBJ);
    }

    private function migrateUsers(): void
    {
        $this->info('遷移用戶...');
        $stmt = $this->wp->query("SELECT * FROM {$this->prefix}users");

        foreach ($stmt as $wpUser) {
            $meta = $this->getUserMeta($wpUser->ID);
            $role = $this->mapRole($meta['wp_capabilities'] ?? '');

            if (!$this->dryRun) {
                $user = User::updateOrCreate(
                    ['email' => $wpUser->user_email],
                    [
                        'name'              => $wpUser->display_name,
                        'password'          => Hash::make(Str::random(16)), // 需要重設密碼
                        'role'              => $role,
                        'email_verified_at' => $wpUser->user_registered,
                        'created_at'        => $wpUser->user_registered,
                    ]
                );
                $this->userMap[$wpUser->ID] = $user->id;
            }

            $this->line("  用戶: {$wpUser->user_email} → {$role}");
        }

        $this->info("  共遷移 " . count($this->userMap) . " 位用戶");
    }

    private function migrateCategories(): void
    {
        $this->info('遷移分類...');
        $sql = "SELECT t.*, tt.description, tt.parent
                FROM {$this->prefix}terms t
                JOIN {$this->prefix}term_taxonomy tt ON t.term_id = tt.term_id
                WHERE tt.taxonomy = 'category' AND t.slug != 'uncategorized'";

        foreach ($this->wp->query($sql) as $wpCat) {
            if (!$this->dryRun) {
                $cat = Category::updateOrCreate(
                    ['slug' => $wpCat->slug],
                    [
                        'name'        => $wpCat->name,
                        'description' => $wpCat->description,
                    ]
                );
                $this->categoryMap[$wpCat->term_id] = $cat->id;
            }
            $this->line("  分類: {$wpCat->name}");
        }
    }

    private function migrateTags(): void
    {
        $this->info('遷移標籤...');
        $sql = "SELECT t.*
                FROM {$this->prefix}terms t
                JOIN {$this->prefix}term_taxonomy tt ON t.term_id = tt.term_id
                WHERE tt.taxonomy = 'post_tag'";

        foreach ($this->wp->query($sql) as $wpTag) {
            if (!$this->dryRun) {
                $tag = \App\Models\Tag::updateOrCreate(
                    ['slug' => $wpTag->slug],
                    ['name' => $wpTag->name]
                );
                $this->tagMap[$wpTag->term_id] = $tag->id;
            }
            $this->line("  標籤: {$wpTag->name}");
        }
    }

    private function migratePosts(): void
    {
        $this->info('遷移文章與頁面...');

        $sql = "SELECT * FROM {$this->prefix}posts
                WHERE post_status IN ('publish', 'draft')
                AND post_type IN ('post', 'page')
                ORDER BY post_date ASC";

        $count = 0;
        foreach ($this->wp->query($sql) as $wpPost) {
            $categoryId = $this->getPostCategory($wpPost->ID);
            $tagIds     = $this->getPostTags($wpPost->ID);
            $userId     = $this->userMap[$wpPost->post_author] ?? 1;

            $slug = $this->ensureUniqueSlug($wpPost->post_name ?: Str::slug($wpPost->post_title));

            $data = [
                'user_id'      => $userId,
                'category_id'  => $categoryId,
                'title'        => $wpPost->post_title,
                'slug'         => $slug,
                'excerpt'      => strip_tags($wpPost->post_excerpt),
                'content'      => $this->convertContent($wpPost->post_content),
                'status'       => $wpPost->post_status === 'publish' ? 'published' : 'draft',
                'type'         => $wpPost->post_type,
                'published_at' => $wpPost->post_status === 'publish' ? $wpPost->post_date : null,
                'created_at'   => $wpPost->post_date,
                'updated_at'   => $wpPost->post_modified,
            ];

            if (!$this->dryRun) {
                $post = Post::create($data);
                if ($tagIds) {
                    $post->tags()->sync($tagIds);
                }
            }

            $count++;
            $this->line("  文章: {$wpPost->post_title}");
        }

        $this->info("  共遷移 {$count} 篇文章");
    }

    // ── Helpers ──────────────────────────────────────────────

    private function getUserMeta(int $userId): array
    {
        $stmt = $this->wp->prepare(
            "SELECT meta_key, meta_value FROM {$this->prefix}usermeta WHERE user_id = ?"
        );
        $stmt->execute([$userId]);
        return array_column($stmt->fetchAll(), 'meta_value', 'meta_key');
    }

    private function mapRole(string $capabilities): string
    {
        if (str_contains($capabilities, 'administrator')) return 'admin';
        if (str_contains($capabilities, 'editor'))        return 'editor';
        if (str_contains($capabilities, 'author'))        return 'author';
        return 'viewer';
    }

    private function getPostCategory(int $postId): ?int
    {
        $sql = "SELECT tt.term_id
                FROM {$this->prefix}term_relationships tr
                JOIN {$this->prefix}term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                WHERE tr.object_id = ? AND tt.taxonomy = 'category'
                LIMIT 1";
        $stmt = $this->wp->prepare($sql);
        $stmt->execute([$postId]);
        $row = $stmt->fetch();
        return $row ? ($this->categoryMap[$row->term_id] ?? null) : null;
    }

    private function getPostTags(int $postId): array
    {
        $sql = "SELECT tt.term_id
                FROM {$this->prefix}term_relationships tr
                JOIN {$this->prefix}term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                WHERE tr.object_id = ? AND tt.taxonomy = 'post_tag'";
        $stmt = $this->wp->prepare($sql);
        $stmt->execute([$postId]);
        return array_filter(array_map(
            fn($r) => $this->tagMap[$r->term_id] ?? null,
            $stmt->fetchAll()
        ));
    }

    private function convertContent(string $content): string
    {
        // Remove WordPress shortcodes
        $content = preg_replace('/\[.*?\]/', '', $content);
        // Fix image paths if needed
        return trim($content);
    }

    private function ensureUniqueSlug(string $slug): string
    {
        $base = $slug ?: 'post';
        $candidate = $base;
        $i = 1;
        while (Post::where('slug', $candidate)->exists()) {
            $candidate = $base . '-' . $i++;
        }
        return $candidate;
    }
}
