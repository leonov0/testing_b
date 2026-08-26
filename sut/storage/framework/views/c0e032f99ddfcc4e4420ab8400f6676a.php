<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Shipments — admin</title>
</head>
<body>
<h1>Shipments</h1>
<table>
    <thead>
    <tr>
        <th>Container</th>
        <th>Consignee</th>
        <th>Status</th>
        <th>Notes</th>
    </tr>
    </thead>
    <tbody>
    <?php $__currentLoopData = $shipments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $shipment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <tr>
            <td><?php echo e($shipment->container_code); ?></td>
            <td><?php echo $shipment->consignee; ?></td>
            <td><?php echo e($shipment->status); ?></td>
            <td><?php echo $shipment->notes; ?></td>
        </tr>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </tbody>
</table>
</body>
</html>
<?php /**PATH /Users/valdisaglonietis/PhpstormProjects/skills/2026_test/module_b/sut/resources/views/admin/shipments.blade.php ENDPATH**/ ?>