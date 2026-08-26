<?php $__env->startSection('title', 'Products'); ?>
<?php $__env->startSection('content'); ?>
    <h2>Products</h2>
    <a href="/products/new">New product</a>

    <form method="get" action="/products" role="search">
        <label for="query">Search products</label>
        <input id="query" name="query" type="search" value="<?php echo e($keyword); ?>">
        <button type="submit">Search</button>
    </form>

    <ul>
        <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <li>
                <a href="/products/<?php echo e($product->gtin); ?>"><?php echo e($product->name_en); ?></a>
                <span><?php echo e($product->gtin); ?></span>
                <span><?php echo e($product->company?->company_name); ?></span>
                <?php if($product->is_hidden): ?><span>Hidden</span><?php endif; ?>
            </li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ul>
    <?php if($products->isEmpty()): ?>
        <p>No products match.</p>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/valdisaglonietis/PhpstormProjects/skills/2026_test/module_b/sut/resources/views/products/index.blade.php ENDPATH**/ ?>