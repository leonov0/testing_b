<?php $__env->startSection('title', 'Admin login'); ?>
<?php $__env->startSection('content'); ?>
    <h2>Admin login</h2>
    <?php if($errors->any()): ?>
        <p role="alert"><?php echo e($errors->first()); ?></p>
    <?php endif; ?>
    <form method="post" action="/login">
        <?php echo csrf_field(); ?>
        <label for="passphrase">Passphrase</label>
        <input id="passphrase" name="passphrase" type="password" autocomplete="current-password">
        <button type="submit">Sign in</button>
    </form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/valdisaglonietis/PhpstormProjects/skills/2026_test/module_b/sut/resources/views/auth/login.blade.php ENDPATH**/ ?>