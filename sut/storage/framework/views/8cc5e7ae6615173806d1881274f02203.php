<?php $__env->startSection('title', 'Deactivated companies'); ?>
<?php $__env->startSection('content'); ?>
    <h2>Deactivated companies</h2>
    <ul>
        <?php $__currentLoopData = $companies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $company): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <li><a href="/companies/<?php echo e($company->id); ?>"><?php echo e($company->company_name); ?></a></li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ul>
    <?php if($companies->isEmpty()): ?>
        <p>No deactivated companies.</p>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/valdisaglonietis/PhpstormProjects/skills/2026_test/module_b/sut/resources/views/companies/deactivated.blade.php ENDPATH**/ ?>