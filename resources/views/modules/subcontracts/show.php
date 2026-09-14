<h2 class="page-title mb-4"><?= e($subcontract['subcontract_code'] . ' - ' . $subcontract['title']) ?></h2>
<div class="card"><div class="card-body">
<p>المشروع: <?= e($subcontract['project_name']) ?></p>
<p>المقاول: <?= e($subcontract['vendor_name']) ?></p>
<p>القيمة: <?= e($subcontract['contract_value'] ?? '—') ?></p>
<div class="d-flex flex-wrap gap-2">
<a class="btn" href="/subcontracts/<?= (int) $subcontract['id'] ?>/edit">تعديل</a>
<a class="btn btn-primary" href="/subcontracts/<?= (int) $subcontract['id'] ?>/boq">BOQ / نطاق الأعمال</a>
<a class="btn btn-primary" href="/subcontracts/<?= (int) $subcontract['id'] ?>/certificates">مستخلصات الأعمال</a>
<a class="btn btn-success" href="/subcontracts/<?= (int) $subcontract['id'] ?>/payments">دفعات المقاول</a>
<?php foreach ($transitions as $status): ?><form class="d-inline js-confirm-form" method="post" action="/subcontracts/<?= (int) $subcontract['id'] ?>/status"><input type="hidden" name="_token" value="<?= e($csrfToken) ?>"><input type="hidden" name="status" value="<?= e($status) ?>"><button class="btn"><?= e($status) ?></button></form><?php endforeach; ?>
</div></div></div>
