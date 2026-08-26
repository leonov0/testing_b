<?php $__env->startSection('title', $company->company_name); ?>
<?php $__env->startSection('content'); ?>
    <h2><?php echo e($company->company_name); ?></h2>
    <dl>
        <dt>Address</dt><dd><?php echo e($company->company_address); ?></dd>
        <dt>Telephone</dt><dd><?php echo e($company->company_telephone); ?></dd>
        <dt>Email</dt><dd><?php echo e($company->company_email); ?></dd>
        <dt>Owner</dt><dd><?php echo e($company->owner_name); ?> — <?php echo e($company->owner_mobile); ?> — <?php echo e($company->owner_email); ?></dd>
        <dt>Contact</dt><dd><?php echo e($company->contact_name); ?> — <?php echo e($company->contact_mobile); ?> — <?php echo e($company->contact_email); ?></dd>
        <dt>Status</dt><dd><?php echo e($company->deactivated ? 'Deactivated' : 'Active'); ?></dd>
    </dl>

    <a href="/companies/<?php echo e($company->id); ?>/edit">Edit company</a>

    <?php if($company->deactivated): ?>
        <form method="post" action="/companies/<?php echo e($company->id); ?>/reactivate">
            <?php echo csrf_field(); ?>
            <button type="submit">Reactivate company</button>
        </form>
    <?php else: ?>
        <form method="post" action="/companies/<?php echo e($company->id); ?>/deactivate">
            <?php echo csrf_field(); ?>
            <button type="submit">Deactivate company</button>
        </form>
    <?php endif; ?>

    <h3>Products</h3>
    <ul>
        <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <li>
                <a href="/products/<?php echo e($product->gtin); ?>"><?php echo e($product->name_en); ?></a>
                <span><?php echo e($product->gtin); ?></span>
                <?php if($product->is_hidden): ?><span>Hidden</span><?php endif; ?>
            </li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ul>
    <?php if($products->isEmpty()): ?>
        <p>No products for this company.</p>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/valdisaglonietis/PhpstormProjects/skills/2026_test/module_b/sut/resources/views/companies/show.blade.php ENDPATH**/ ?>