<?php
$statuses = ['draft' => 'مسودة', 'active' => 'نشط', 'suspended' => 'معلق', 'completed' => 'مكتمل', 'cancelled' => 'ملغي'];
$projects = $sections = $vendors = [];
foreach ($rows as $row) {
    $projects[(int) $row['project_id']] = $row['project_name'];
    $vendors[(int) $row['vendor_id']] = $row['vendor_name'];
    if ($row['work_section_id'] !== null) $sections[(int) $row['work_section_id']] = $row['work_section_name'];
}
asort($projects); asort($sections); asort($vendors);
?>
<div class="page-header mb-4"><div class="row align-items-center"><div class="col"><h2 class="page-title">عقود مقاولي الباطن</h2></div><div class="col-auto"><a class="btn btn-primary" href="/subcontracts/create">إضافة عقد</a></div></div></div>
<div class="card">
<div class="card-body border-bottom"><div class="row g-3">
<div class="col-md-3"><label class="form-label" for="subcontract-project-filter">المشروع</label><select id="subcontract-project-filter" class="form-select js-tom-select"><option value="">كل المشروعات</option><?php foreach ($projects as $id => $name): ?><option value="<?= $id ?>"><?= e($name) ?></option><?php endforeach; ?></select></div>
<div class="col-md-3"><label class="form-label" for="subcontract-section-filter">مجال العمل</label><select id="subcontract-section-filter" class="form-select js-tom-select"><option value="">كل مجالات العمل</option><?php foreach ($sections as $id => $name): ?><option value="<?= $id ?>"><?= e($name) ?></option><?php endforeach; ?></select></div>
<div class="col-md-3"><label class="form-label" for="subcontract-vendor-filter">مقاول الباطن</label><select id="subcontract-vendor-filter" class="form-select js-tom-select"><option value="">كل المقاولين</option><?php foreach ($vendors as $id => $name): ?><option value="<?= $id ?>"><?= e($name) ?></option><?php endforeach; ?></select></div>
<div class="col-md-3"><label class="form-label" for="subcontract-status-filter">الحالة</label><select id="subcontract-status-filter" class="form-select"><option value="">كل الحالات</option><?php foreach ($statuses as $value => $label): ?><option value="<?= e($value) ?>"><?= e($label) ?></option><?php endforeach; ?></select></div>
</div></div>
<div class="table-responsive p-3"><table id="subcontracts-table" class="table table-vcenter card-table w-100"><thead><tr><th>الكود</th><th>المشروع</th><th>مجال العمل</th><th>المقاول</th><th>اسم العقد / نطاق الأعمال</th><th>القيمة</th><th>التاريخ</th><th>الحالة</th></tr></thead><tbody>
<?php foreach ($rows as $row): ?><tr><td><a href="/subcontracts/<?= (int) $row['id'] ?>"><?= e($row['subcontract_code']) ?></a></td><td data-search="<?= (int) $row['project_id'] ?>"><?= e($row['project_name']) ?></td><td data-search="<?= (int) ($row['work_section_id'] ?? 0) ?>"><?= e($row['work_section_name'] ?? '—') ?></td><td data-search="<?= (int) $row['vendor_id'] ?>"><?= e($row['vendor_name']) ?></td><td><?= e($row['title']) ?></td><td><?= e($row['contract_value'] ?? '—') ?></td><td><?= e($row['contract_date']) ?></td><td data-search="<?= e($row['status']) ?>"><span class="badge bg-blue-lt"><?= e($statuses[$row['status']] ?? $row['status']) ?></span></td></tr><?php endforeach; ?>
</tbody></table></div></div>
<script src="/assets/js/modules/subcontracts-table.js" defer></script>
