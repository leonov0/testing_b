<?php $__env->startSection('title', $product->name_en); ?>
<?php $__env->startSection('content'); ?>
    <h2><?php echo $product->name_en; ?></h2>
    <p><?php echo e($product->name_fr); ?></p>

    <?php if($product->image_path): ?>
        <img src="<?php echo e(asset('storage/'.$product->image_path)); ?>" alt="<?php echo e($product->name_en); ?>">
        <form method="post" action="/products/<?php echo e($product->gtin); ?>/remove-image">
            <?php echo csrf_field(); ?>
            <button type="submit">Remove image</button>
        </form>
    <?php else: ?>
        <img src="/images/product-placeholder.svg" alt="No product image uploaded">
    <?php endif; ?>

    <dl>
        <dt>GTIN</dt><dd><?php echo e($product->gtin); ?></dd>
        <dt>Brand</dt><dd><?php echo e($product->brand); ?></dd>
        <dt>Country of origin</dt><dd><?php echo e($product->country_of_origin); ?></dd>
        <dt>Gross weight</dt><dd><?php echo e($product->weight_gross); ?> <?php echo e($product->weight_unit); ?></dd>
        <dt>Net content</dt><dd><?php echo e($product->weight_net); ?> <?php echo e($product->weight_unit); ?></dd>
        <dt>Company</dt><dd><a href="/companies/<?php echo e($product->company_id); ?>"><?php echo e($product->company?->company_name); ?></a></dd>
        <dt>Status</dt><dd><?php echo e($product->is_hidden ? 'Hidden' : 'Visible'); ?></dd>
    </dl>

    <h3>Description</h3>
    <p><?php echo $product->description_en; ?></p>
    <p lang="fr"><?php echo e($product->description_fr); ?></p>

    <a href="/products/<?php echo e($product->gtin); ?>/edit">Edit product</a>

    <?php if($product->is_hidden): ?>
        <form method="post" action="/products/<?php echo e($product->gtin); ?>/unhide">
            <?php echo csrf_field(); ?>
            <button type="submit">Unhide product</button>
        </form>
        <form method="post" action="/products/<?php echo e($product->gtin); ?>">
            <?php echo csrf_field(); ?>
            <?php echo method_field('DELETE'); ?>
            <button type="submit">Delete product permanently</button>
        </form>
    <?php else: ?>
        <form method="post" action="/products/<?php echo e($product->gtin); ?>/hide">
            <?php echo csrf_field(); ?>
            <button type="submit">Hide product</button>
        </form>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/valdisaglonietis/PhpstormProjects/skills/2026_test/module_b/sut/resources/views/products/show.blade.php ENDPATH**/ ?>