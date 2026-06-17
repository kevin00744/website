<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        $admin = User::create([
            'name'              => 'Administrator',
            'email'             => 'admin@example.com',
            'password'          => Hash::make('password'),
            'role'              => 'admin',
            'email_verified_at' => now(),
        ]);

        // Default categories
        $categories = ['技術', '設計', '商業', '生活'];
        foreach ($categories as $name) {
            Category::create(['name' => $name]);
        }

        // Default tags
        $tags = ['Laravel', 'Vue', 'JavaScript', 'PHP', 'Docker'];
        foreach ($tags as $name) {
            Tag::create(['name' => $name]);
        }

        // Sample post
        Post::create([
            'user_id'      => $admin->id,
            'category_id'  => 1,
            'title'        => '歡迎使用自製 CMS',
            'slug'         => 'welcome',
            'excerpt'      => '這是您的第一篇文章。',
            'content'      => '<p>歡迎使用自製 Laravel CMS 系統！</p><p>您可以在管理後台建立、編輯並發布您的內容。</p>',
            'status'       => 'published',
            'type'         => 'post',
            'published_at' => now(),
        ]);

        // Default settings
        \DB::table('settings')->insert([
            ['key' => 'site_name',        'value' => '我的網站',     'group' => 'general'],
            ['key' => 'site_description', 'value' => '網站描述',     'group' => 'general'],
            ['key' => 'posts_per_page',   'value' => '10',           'group' => 'general'],
            ['key' => 'allow_comments',   'value' => 'true',         'group' => 'general'],
        ]);
    }
}
