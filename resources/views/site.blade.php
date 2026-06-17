<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>璨妍 TSANYEN</title>

    {{-- Elementor CSS --}}
    <link rel="stylesheet" href="/wp-content/themes/hello-elementor/assets/css/reset.css">
    <link rel="stylesheet" href="/wp-content/themes/hello-elementor/assets/css/theme.css">
    <link rel="stylesheet" href="/wp-content/themes/hello-elementor/assets/css/header-footer.css">
    <link rel="stylesheet" href="/wp-content/plugins/elementor/assets/css/frontend.min.css">
    <link rel="stylesheet" href="/wp-content/plugins/elementor/assets/css/widget-image.min.css">
    <link rel="stylesheet" href="/wp-content/plugins/elementor/assets/css/widget-heading.min.css">
    <link rel="stylesheet" href="/wp-content/plugins/elementor/assets/css/widget-spacer.min.css">
    <link rel="stylesheet" href="/wp-content/plugins/elementor/assets/css/widget-divider.min.css">
    <link rel="stylesheet" href="/wp-content/plugins/elementor/assets/css/widget-icon-box.min.css">
    <link rel="stylesheet" href="/wp-content/plugins/elementor/assets/css/widget-social-icons.min.css">
    <link rel="stylesheet" href="/wp-content/plugins/elementor/assets/css/widget-nested-accordion.min.css">
    <link rel="stylesheet" href="/wp-content/plugins/elementor/assets/css/conditionals/apple-webkit.min.css">

    {{-- 所有 Elementor post CSS --}}
    @foreach(glob(public_path('wp-content/uploads/elementor/css/post-*.css')) as $cssFile)
        <link rel="stylesheet" href="/wp-content/uploads/elementor/css/{{ basename($cssFile) }}">
    @endforeach

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Roboto+Slab:300,400,700&display=swap" rel="stylesheet">

    @vite(['resources/js/app.js'])
    @inertiaHead
</head>
<body>
    @inertia
</body>
</html>
