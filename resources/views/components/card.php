<section class="card <?= e($class ?? '') ?>">
    <?php if (!empty($title)): ?><div class="card-header"><h2 class="card-title"><?= e($title) ?></h2></div><?php endif; ?>
    <div class="card-body"><?= $slot ?? '' ?></div>
</section>
