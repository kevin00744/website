<?php

namespace App\Support;

/**
 * 原網站（tsanyen.com）各頁面實際載入的 Elementor CSS 清單，
 * 依 wget 匯出結果還原。同時供前台渲染與後台 GrapesJS 編輯器預覽使用，
 * 確保編輯畫面跟正式頁面視覺一致。
 */
class SiteCss
{
    private const BASE = [
        '/wp-content/themes/hello-elementor/assets/css/reset.css',
        '/wp-content/themes/hello-elementor/assets/css/theme.css',
        '/wp-content/themes/hello-elementor/assets/css/header-footer.css',
        '/wp-content/plugins/elementor/assets/css/frontend.min.css',
        '/wp-content/uploads/elementor/css/post-7.css',
        '/wp-content/plugins/elementor/assets/css/widget-image.min.css',
        '/wp-content/plugins/elementor-pro/assets/css/widget-nav-menu.min.css',
        '/wp-content/plugins/elementor/assets/css/widget-spacer.min.css',
        '/wp-content/plugins/elementor/assets/css/widget-heading.min.css',
        '/wp-content/plugins/elementor/assets/css/widget-divider.min.css',
        '/wp-content/plugins/elementor/assets/css/widget-social-icons.min.css',
        '/wp-content/plugins/elementor/assets/css/conditionals/apple-webkit.min.css',
        '/wp-content/uploads/elementor/css/post-27.css',
        '/wp-content/uploads/elementor/css/post-35.css',
    ];

    private const PER_PAGE = [
        'home'    => ['/wp-content/plugins/elementor/assets/css/widget-icon-box.min.css', '/wp-content/plugins/elementor/assets/css/widget-nested-accordion.min.css', '/wp-content/uploads/elementor/css/post-19.css'],
        'about'   => ['/wp-content/plugins/elementor/assets/css/widget-icon-box.min.css', '/wp-content/uploads/elementor/css/post-57.css'],
        'face'    => ['/wp-content/plugins/elementor/assets/css/widget-icon-box.min.css', '/wp-content/uploads/elementor/css/post-357.css'],
        'hair'    => ['/wp-content/plugins/elementor/assets/css/widget-icon-box.min.css', '/wp-content/uploads/elementor/css/post-63.css'],
        'tech'    => ['/wp-content/uploads/elementor/css/post-59.css'],
        'contact' => ['/wp-content/plugins/elementor-pro/assets/css/widget-form.min.css', '/wp-content/uploads/elementor/css/post-69.css'],
    ];

    private const GOOGLE_FONTS = [
        'https://fonts.googleapis.com/css?family=Roboto:300,400,500,700&display=swap',
        'https://fonts.googleapis.com/css?family=Roboto+Slab:300,400,700&display=swap',
    ];

    public static function for(?string $slug): array
    {
        return [...self::BASE, ...(self::PER_PAGE[$slug] ?? []), ...self::GOOGLE_FONTS];
    }
}
