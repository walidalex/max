<?php $statuses = [
    "draft" => "مسودة",
    "posted" => "مرحّل",
    "cancelled" => "ملغى",
];
$methods = [
    "cash" => "نقدي",
    "bank_transfer" => "تحويل بنكي",
    "cheque" => "شيك",
    "other" => "أخرى",
];
?>
<div class="page-header mb-4"><div class="row align-items-center"><div class="col"><div class="page-pretitle"><?= e(
    $receipt["receipt_code"],
) ?></div><h2 class="page-title">تفاصيل سند القبض</h2></div><div class="col-auto d-flex gap-2"><a class="btn" href="/client-contracts/<?= (int) $receipt[
    "client_contract_id"
] ?>/receipts">سندات العقد</a><?php
if (
    $receipt["status"] === "draft" &&
    $canEdit
): ?><a class="btn" href="/client-receipts/<?= (int) $receipt[
    "id"
] ?>/edit">تعديل</a><?php endif;
if (
    $receipt["status"] === "posted"
): ?><a class="btn" href="/client-receipts/<?= (int) $receipt[
    "id"
] ?>/print">طباعة</a><?php endif;
if (
    $receipt["status"] === "posted" &&
    $canAllocate &&
    (string) $receipt["unallocated_amount"] !== "0.00"
): ?><a class="btn btn-primary" href="/client-receipts/<?= (int) $receipt[
    "id"
] ?>/allocate">تخصيص مبلغ</a><?php endif;
?></div></div></div>
<div class="row g-3 mb-4"><?php foreach (
    [
        "amount" => "مبلغ السند",
        "allocated_amount" => "المخصص",
        "unallocated_amount" => "غير المخصص",
    ]
    as $key => $label
): ?><div class="col-md-4"><div class="card card-sm"><div class="card-body"><div class="text-secondary"><?= $label ?></div><div class="h2 mb-0"><?= e(
    (string) ($receipt[$key] ?? "—"),
) ?></div></div></div></div><?php endforeach; ?></div>
<div class="card mb-4"><div class="card-body"><div class="row g-3"><?php
$posted = $receipt["status"] === "posted";
foreach (
    [
        "رقم السند" => $receipt["receipt_number"] ?: "—",
        "التاريخ" => $receipt["receipt_date"],
        "العميل" => $posted
            ? $receipt["client_name_snapshot"]
            : $receipt["client_name"],
        "المشروع" => $posted
            ? $receipt["project_code_snapshot"] .
                " - " .
                $receipt["project_name_snapshot"]
            : $receipt["project_code"] . " - " . $receipt["project_name"],
        "العقد" => $posted
            ? $receipt["contract_code_snapshot"] .
                " - " .
                ($receipt["contract_number_snapshot"] ??
                    ($receipt["contract_title_snapshot"] ?? ""))
            : $receipt["contract_code"] .
                " - " .
                ($receipt["contract_number"] ?? $receipt["contract_title"]),
        "طريقة الدفع" =>
            $methods[$receipt["payment_method"]] ?? $receipt["payment_method"],
        "المرجع" => $receipt["reference_number"] ?: "—",
        "الحالة" => $statuses[$receipt["status"]] ?? $receipt["status"],
        "أنشأ بواسطة" => $receipt["created_by_name"],
        "رُحّل بواسطة" => $receipt["posted_by_name"] ?: "—",
    ]
    as $label => $value
): ?><div class="col-md-4"><div class="text-secondary small"><?= $label ?></div><div class="fw-bold"><?= e(
    (string) $value,
) ?></div></div><?php endforeach;
?></div><?php if ($receipt["notes"]): ?><hr><div><?= nl2br(
    e($receipt["notes"]),
) ?></div><?php endif; ?></div></div>
<?php if ($receipt["status"] === "draft"): ?><div class="d-flex gap-2"><?php
if (
    $canPost
): ?><form class="js-confirm-form" method="post" action="/client-receipts/<?= (int) $receipt[
    "id"
] ?>/post" data-confirm="سيتم ترحيل السند وتجميد بياناته المالية."><input type="hidden" name="_token" value="<?= e(
    $csrfToken,
) ?>"><button class="btn btn-success">ترحيل السند</button></form><?php endif;
if (
    $canCancel
): ?><form class="js-confirm-form" method="post" action="/client-receipts/<?= (int) $receipt[
    "id"
] ?>/cancel" data-confirm="سيتم إلغاء مسودة سند القبض."><input type="hidden" name="_token" value="<?= e(
    $csrfToken,
) ?>"><button class="btn btn-danger">إلغاء المسودة</button></form><?php endif;
?></div><?php endif; ?>
<div class="card mt-4"><div class="card-header"><h3 class="card-title">سجل التخصيصات</h3></div><div class="table-responsive"><table class="table card-table"><thead><tr><th>المستخلص</th><th>رقم المستخلص</th><th>التسلسل</th><th>التاريخ</th><th>المبلغ المخصص</th><th>خُصص بواسطة</th></tr></thead><tbody><?php
if (
    !$allocations
): ?><tr><td colspan="6" class="text-center text-secondary">لا توجد تخصيصات.</td></tr><?php endif;
foreach ($allocations as $allocation): ?><tr><td><?= e(
    $allocation["statement_code_snapshot"],
) ?></td><td><?= e(
    $allocation["statement_number_snapshot"] ?? "—",
) ?></td><td><?= e(
    (string) $allocation["statement_sequence_snapshot"],
) ?></td><td><?= e($allocation["statement_date_snapshot"]) ?></td><td><?= e(
    $allocation["allocated_amount"],
) ?></td><td><?= e(
    $allocation["allocated_by_name"],
) ?></td></tr><?php endforeach;
?></tbody></table></div></div>
