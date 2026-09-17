<?php
$editing = is_array($subcontract);
$value = static fn(string $key): string => (string) ($subcontract[$key] ?? '');
$locked = $editing && $value('status') !== 'draft';
$eligibleVendors = array_values(array_filter($vendors, static fn(array $vendor): bool => (bool) $vendor['is_active']));
$missingProjects = !$editing && $projects === [];
$missingVendors = !$editing && $eligibleVendors === [];
$cannotCreate = $missingProjects || $missingVendors;
?>
<h2 class="page-title mb-4"><?= e($title) ?></h2>
<?php if ($cannotCreate): ?>
<div class="alert alert-warning"><h4 class="alert-title">لا يمكن إنشاء عقد مقاول باطن بعد</h4><ul class="mb-0"><?php if ($missingProjects): ?><li>أنشئ مشروعًا أولًا.</li><?php endif; ?><?php if ($missingVendors): ?><li>أنشئ موردًا نشطًا من النوع «مقاول باطن» أو «كلاهما» أولًا.</li><?php endif; ?></ul></div>
<?php endif; ?>
<form class="card" method="post" action="<?= $editing ? '/subcontracts/' . (int) $subcontract['id'] . '/edit' : '/subcontracts' ?>">
<div class="card-body"><input type="hidden" name="_token" value="<?= e($csrfToken) ?>"><div class="row g-3">
<div class="col-md-6"><label class="form-label required">المشروع</label><select class="form-select js-tom-select" name="project_id" required <?= $locked ? 'disabled' : '' ?>><option value="">اختر المشروع</option><?php foreach ($projects as $project): ?><option value="<?= (int) $project['id'] ?>" <?= $value('project_id') === (string) $project['id'] ? 'selected' : '' ?>><?= e($project['project_code'] . ' - ' . $project['name']) ?></option><?php endforeach; ?></select><?php if ($locked): ?><input type="hidden" name="project_id" value="<?= e($value('project_id')) ?>"><?php endif; ?></div>
<div class="col-md-6"><label class="form-label required">مقاول الباطن</label><select class="form-select js-tom-select" name="vendor_id" required <?= $locked ? 'disabled' : '' ?>><option value="">اختر مقاول الباطن</option><?php foreach ($vendors as $vendor): if (!$vendor['is_active'] && $value('vendor_id') !== (string) $vendor['id']) continue; ?><option value="<?= (int) $vendor['id'] ?>" <?= $value('vendor_id') === (string) $vendor['id'] ? 'selected' : '' ?>><?= e($vendor['vendor_code'] . ' - ' . $vendor['name']) ?></option><?php endforeach; ?></select><?php if ($locked): ?><input type="hidden" name="vendor_id" value="<?= e($value('vendor_id')) ?>"><?php endif; ?></div>
<?php foreach (['subcontract_number'=>'رقم العقد التجاري','title'=>'العنوان','contract_date'=>'تاريخ العقد','start_date'=>'تاريخ البدء','expected_end_date'=>'الانتهاء المتوقع','contract_value'=>'قيمة العقد'] as $key=>$label): ?><div class="col-md-6"><label class="form-label <?= in_array($key,['title','contract_date'],true)?'required':'' ?>"><?= $label ?></label><input class="form-control" name="<?= $key ?>" <?= $key === 'contract_date' || str_contains($key, 'date') ? 'type="date"' : '' ?> value="<?= e($value($key)) ?>" <?= in_array($key,['title','contract_date'],true)?'required':'' ?> <?= $locked && in_array($key,['subcontract_number','title','contract_date','contract_value'],true) ? 'readonly' : '' ?>></div><?php endforeach; ?>
<div class="col-md-6"><label class="form-label required">طريقة التسعير</label><select name="pricing_method" class="form-select" required <?= $locked ? 'disabled' : '' ?>><option value="boq">جدول كميات</option><option value="lump_sum" <?= $value('pricing_method') === 'lump_sum' ? 'selected' : '' ?>>مقطوعية</option></select><?php if ($locked): ?><input type="hidden" name="pricing_method" value="<?= e($value('pricing_method')) ?>"><?php endif; ?></div>
<div class="col-12"><label class="form-label">الوصف</label><textarea class="form-control" name="description" <?= $locked ? 'readonly' : '' ?>><?= e($value('description')) ?></textarea></div><div class="col-12"><label class="form-label">ملاحظات</label><textarea class="form-control" name="notes"><?= e($value('notes')) ?></textarea></div>
</div></div><div class="card-footer"><button class="btn btn-primary" <?= $cannotCreate ? 'disabled' : '' ?>>حفظ</button></div></form>
