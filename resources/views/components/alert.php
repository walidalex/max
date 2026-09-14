<?php if (!empty($message)): ?>
<div class="alert alert-<?= e($type ?? 'info') ?>" role="alert"><?= e($message) ?></div>
<?php endif; ?>
