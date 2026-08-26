<!doctype html>
<html lang="<?php echo e($lang ?? 'en'); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $__env->yieldContent('title', 'Made in France — product catalogue'); ?></title>
    <?php echo $__env->yieldPushContent('head'); ?>
</head>
<body>
<header>
    <h1>Made in France — product catalogue</h1>
    <nav aria-label="Main">
        <a href="/products">Products</a>
        <a href="/companies">Companies</a>
        <a href="/companies/deactivated">Deactivated companies</a>
        <a href="/verify">GTIN verification</a>
    </nav>
</header>
<main>
    <?php echo $__env->yieldContent('content'); ?>
</main>
</body>
</html>
<?php /**PATH /Users/valdisaglonietis/PhpstormProjects/skills/2026_test/module_b/sut/resources/views/layouts/app.blade.php ENDPATH**/ ?>