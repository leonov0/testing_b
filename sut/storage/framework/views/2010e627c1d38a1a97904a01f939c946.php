<?php
    $isFrench = $language === 'fr';
    $name = $isFrench ? $product->name_fr : $product->name_en;
    $description = $isFrench ? $product->description_fr : $product->description_en;
?>
<!doctype html>
<html lang="<?php echo e($isFrench ? 'fr' : 'en'); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e($name); ?></title>
</head>
<body>
<nav aria-label="Language">
    <a href="?lang=en" lang="en" hreflang="en">English</a>
    <a href="?lang=fr" lang="fr" hreflang="fr">Français</a>
</nav>

<main>
    <h1><?php echo $name; ?></h1>
    <p class="company-name"><?php echo e($product->company?->company_name); ?></p>

    <?php if($product->image_path): ?>
        <img src="<?php echo e(asset('storage/'.$product->image_path)); ?>" alt="<?php echo e($name); ?>">
    <?php else: ?>
        <img src="/images/product-placeholder.svg" alt="<?php echo e($name); ?>">
    <?php endif; ?>

    <dl>
        <dt><?php echo e($isFrench ? 'Code GTIN' : 'GTIN'); ?></dt>
        <dd class="gtin"><?php echo e($product->gtin); ?></dd>

        <dt><?php echo e($isFrench ? 'Poids brut' : 'Gross weight'); ?></dt>
        <dd class="weight-gross"><?php echo e($product->weight_gross); ?> <?php echo e($product->weight_unit); ?></dd>

        <dt><?php echo e($isFrench ? 'Contenu net' : 'Net content'); ?></dt>
        <dd class="weight-net"><?php echo e($product->weight_net); ?> <?php echo e($product->weight_unit); ?></dd>
    </dl>

    <p class="description" lang="<?php echo e($isFrench ? 'fr' : 'en'); ?>"><?php echo e($description); ?></p>
</main>
</body>
</html>
<?php /**PATH /Users/valdisaglonietis/PhpstormProjects/skills/2026_test/module_b/sut/resources/views/public/product.blade.php ENDPATH**/ ?>