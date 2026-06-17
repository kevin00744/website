<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>璨妍 TSANYEN</title>

    
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

    
    <?php $__currentLoopData = glob(public_path('wp-content/uploads/elementor/css/post-*.css')); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cssFile): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <link rel="stylesheet" href="/wp-content/uploads/elementor/css/<?php echo e(basename($cssFile)); ?>">
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Roboto+Slab:300,400,700&display=swap" rel="stylesheet">

    <?php echo app('Illuminate\Foundation\Vite')(['resources/js/app.js']); ?>
    <?php $__inertiaSsrResponse = app(\Inertia\Ssr\SsrState::class)->setPage($page)->dispatch();  if ($__inertiaSsrResponse) { echo $__inertiaSsrResponse->head; } ?>
</head>
<body>
    <?php $__inertiaSsrResponse = app(\Inertia\Ssr\SsrState::class)->setPage($page)->dispatch();  if ($__inertiaSsrResponse) { echo $__inertiaSsrResponse->body; } else { ?><script data-page="app" type="application/json"><?php echo json_encode($page); ?></script><div id="app"></div><?php } ?>
</body>
</html>
<?php /**PATH /var/www/resources/views/site.blade.php ENDPATH**/ ?>