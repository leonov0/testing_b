<?php $__env->startSection('title', 'Companies'); ?>
<?php $__env->startSection('content'); ?>
    <h2>Companies</h2>
    <a href="/companies/new">New company</a>
    <ul>
        <?php $__currentLoopData = $companies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $company): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <li>
                <a href="/companies/<?php echo e($company->id); ?>"><?php echo e($company->company_name); ?></a>
                <span><?php echo e($company->company_email); ?></span>
            </li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ul>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/valdisaglonietis/PhpstormProjects/skills/2026_test/module_b/sut/resources/views/companies/index.blade.php ENDPATH**/ ?>