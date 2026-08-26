<?php $__env->startSection('title', 'GTIN verification'); ?>
<?php $__env->startSection('content'); ?>
    <h2>GTIN bulk verification</h2>

    <form method="post" action="/verify">
        <?php echo csrf_field(); ?>
        <label for="gtins">GTIN codes, one per line</label>
        <textarea id="gtins" name="gtins" rows="8"><?php echo e($input); ?></textarea>
        <button type="submit">Verify</button>
    </form>

    <?php if(isset($results)): ?>
        <?php if(($allValid ?? false)): ?>
            <p class="all-valid" role="status"><span aria-hidden="true">&check;</span> All valid</p>
        <?php endif; ?>

        <ul class="verification-results">
            <?php $__currentLoopData = $results; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $result): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li data-gtin="<?php echo e($result['gtin']); ?>">
                    <span><?php echo e($result['gtin']); ?></span>
                    <span><?php echo e($result['valid'] ? 'Valid' : 'Not valid'); ?></span>
                </li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>

        <?php if($results === []): ?>
            <p>No GTIN codes submitted.</p>
        <?php endif; ?>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/valdisaglonietis/PhpstormProjects/skills/2026_test/module_b/sut/resources/views/public/verify.blade.php ENDPATH**/ ?>