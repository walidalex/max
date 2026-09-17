<?php if ($canViewReceipts): ?>
<div class="card mb-4"><div class="card-header"><h3 class="card-title">سندات القبض</h3><a class="btn btn-outline-primary me-auto" href="/client-contracts/<?= (int) $contract[
    "id"
] ?>/receipts">فتح سندات القبض</a></div><div class="card-body"><?php if (
    $receiptSummary["inconsistent"]
): ?><div class="alert alert-danger">يوجد عدم اتساق مالي في تسويات العقد ويجب مراجعته.</div><?php endif; ?><div class="row g-3"><?php foreach (
    [
        "approved_claims" => "إجمالي المستخلصات المعتمدة",
        "posted_receipts" => "إجمالي المقبوضات",
        "allocated_receipts" => "المبالغ المخصصة",
        "statement_outstanding" => "الرصيد المستحق",
        "unallocated_receipts" => "المقبوضات غير المخصصة",
    ]
    as $key => $label
): ?><div class="col"><div class="text-secondary small"><?= $label ?></div><div class="fw-bold"><?= e(
    $receiptSummary[$key],
) ?></div></div><?php endforeach; ?></div></div></div>
<?php endif; ?>
<?php
$methods = [
    "cost_plus" => "تكلفة فعلية + نسبة",
    "boq" => "جدول كميات",
    "lump_sum" => "مقطوعية",
];
$statuses = [
    "draft" => "مسودة",
    "active" => "نشط",
    "suspended" => "معلق",
    "completed" => "مكتمل",
    "cancelled" => "ملغي",
];
?>
<div class="page-header mb-4"><div class="row align-items-center"><div class="col"><div class="page-pretitle"><?= e(
    $contract["contract_code"],
) ?></div><h2 class="page-title"><?= e(
    $contract["title"],
) ?></h2></div><div class="col-auto d-flex gap-2"><?php
if ($canEdit): ?><a class="btn" href="/contracts/<?= (int) $contract[
    "id"
] ?>/edit">تعديل</a><?php endif;
if (
    $canChangeStatus &&
    $transitions
): ?><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#status-modal">تغيير الحالة</button><?php endif;
?></div></div></div>
<div class="card"><div class="card-body"><div class="row g-4"><?php foreach (
    [
        "رقم العقد التجاري" => $contract["contract_number"] ?: "—",
        "المشروع" =>
            $contract["project_code"] . " - " . $contract["project_name"],
        "العميل" => $contract["client_code"] . " - " . $contract["client_name"],
        "جهة الاتصال" => $contract["contact_name"] ?: "—",
        "طريقة التسعير" =>
            $methods[$contract["pricing_method"]] ??
            $contract["pricing_method"],
        "تاريخ العقد" => $contract["contract_date"],
        "قيمة العقد" => $contract["contract_value"] ?? "—",
        "نسبة الزيادة" => $contract["markup_percentage"] ?? "—",
        "الحالة" => $statuses[$contract["status"]] ?? $contract["status"],
    ]
    as $label => $value
): ?><div class="col-md-4"><div class="text-secondary small"><?= $label ?></div><div class="fw-bold"><?= e(
    (string) $value,
) ?></div></div><?php endforeach; ?></div><?php foreach (
    ["الوصف" => "description", "الشروط" => "terms", "الملاحظات" => "notes"]
    as $label => $key
):
    if (!$contract[$key]) {
        continue;
    } ?><hr><h4><?= $label ?></h4><div class="text-wrap"><?= nl2br(
    e($contract[$key]),
) ?></div><?php
endforeach; ?></div></div>
<?php if (
    $canChangeStatus &&
    $transitions
): ?><div class="modal fade" id="status-modal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form class="modal-content js-confirm-form" method="post" action="/contracts/<?= (int) $contract[
    "id"
] ?>/status" data-confirm="سيتم تغيير حالة العقد وفق قواعد دورة الاعتماد."><div class="modal-header"><h5 class="modal-title">تغيير حالة العقد</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><input type="hidden" name="_token" value="<?= e(
    $csrfToken,
) ?>"><select class="form-select" name="status" required><?php foreach (
    $transitions
    as $status
): ?><option value="<?= $status ?>"><?= $statuses[
    $status
] ?></option><?php endforeach; ?></select></div><div class="modal-footer"><button type="button" class="btn" data-bs-dismiss="modal">إلغاء</button><button class="btn btn-primary">تأكيد</button></div></form></div></div><?php endif; ?>
<?php if (
    $canViewVariations
): ?><div class="card mt-4"><div class="card-body d-flex align-items-center"><div><h3 class="card-title mb-1">الملحقات والأعمال الإضافية</h3><div class="text-secondary">عرض الملاحق والتغييرات التجارية المرتبطة بهذا العقد.</div></div><a class="btn btn-outline-primary me-auto" href="/contracts/<?= (int) $contract[
    "id"
] ?>/variations">فتح الملحقات</a></div></div><?php endif; ?>
<?php if (
    $canViewProgressStatements
): ?><div class="card mt-4"><div class="card-body d-flex align-items-center"><div><h3 class="card-title mb-1">مستخلصات العميل</h3><div class="text-secondary">عرض المطالبات المرحلية والقيم السابقة والحالية والتراكمية.</div></div><a class="btn btn-outline-primary me-auto" href="/client-contracts/<?= (int) $contract[
    "id"
] ?>/progress-statements">فتح المستخلصات</a></div></div><?php endif; ?>
<?php if (
    $canViewBoq
): ?><div class="card mt-4"><div class="card-body d-flex align-items-center"><div><h3 class="card-title mb-1">جدول الكميات / نطاق الأعمال</h3><div class="text-secondary">النطاق التعاقدي الأصلي والبنود القابلة للقياس.</div></div><a class="btn btn-outline-primary me-auto" href="/contracts/<?= (int) $contract[
    "id"
] ?>/boq">فتح BOQ</a></div></div><?php endif; ?>
