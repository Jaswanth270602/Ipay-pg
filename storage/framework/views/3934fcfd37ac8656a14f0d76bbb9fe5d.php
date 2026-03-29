<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps(['items' => []]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps(['items' => []]); ?>
<?php foreach (array_filter((['items' => []]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if(isset($item['url']) && $idx < count($items)-1): ?>
                <li class="breadcrumb-item"><a href="<?php echo e($item['url']); ?>"><?php echo e($item['label']); ?></a></li>
            <?php else: ?>
                <li class="breadcrumb-item active"><?php echo e($item['label']); ?></li>
            <?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ol>
</nav>


<?php /**PATH C:\Users\dell\Downloads\Ipay-pg\resources\views/components/breadcrumbs.blade.php ENDPATH**/ ?>