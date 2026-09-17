<div class="page-header mb-4"><div class="row align-items-center"><div class="col"><div class="page-pretitle"><?= $contract
    ? e($contract["contract_code"])
    : "السجل العام" ?></div><h2 class="page-title"><?= e(
    $title,
) ?></h2></div><div class="col-auto d-flex gap-2"><?php
if ($contract): ?><a class="btn" href="/contracts/<?= (int) $contract[
    "id"
] ?>">العقد</a><?php endif;
if (
    $canCreate
): ?><a class="btn btn-primary" href="/client-contracts/<?= (int) $contract[
    "id"
] ?>/receipts/create">إضافة سند قبض</a><?php endif;
?></div></div></div>
<?php if ($summary): ?><div class="row g-3 mb-4"><?php foreach (
    [
        "approved_claims" => "إجمالي المستخلصات المعتمدة",
        "posted_receipts" => "إجمالي المقبوضات",
        "allocated_receipts" => "المبالغ المخصصة",
        "statement_outstanding" => "الرصيد المستحق",
        "unallocated_receipts" => "المقبوضات غير المخصصة",
    ]
    as $key => $label
): ?><div class="col"><div class="card card-sm"><div class="card-body"><div class="text-secondary"><?= $label ?></div><div class="h3"><?= e(
    $summary[$key],
) ?></div></div></div></div><?php endforeach; ?></div><?php if (
    $summary["inconsistent"]
): ?><div class="alert alert-danger">يوجد عدم اتساق مالي في تسويات العقد. تم إيقاف التخصيصات الجديدة حتى مراجعة البيانات.</div><?php endif;endif; ?>
<div class="card" id="client-receipts-card" data-url="<?= $contract
    ? "/api/client-contracts/" . (int) $contract["id"] . "/receipts"
    : "/api/client-receipts" ?>"><div class="card-body border-bottom"><div class="row g-2"><?php if (
    !$contract
): ?><div class="col-md-2"><select id="receipt-client" class="form-select"><option value="">كل العملاء</option><?php foreach (
    $references["clients"]
    as $item
): ?><option value="<?= (int) $item["id"] ?>"><?= e(
    $item["client_code"] . " - " . $item["name"],
) ?></option><?php endforeach; ?></select></div><div class="col-md-2"><select id="receipt-project" class="form-select"><option value="">كل المشاريع</option><?php foreach (
    $references["projects"]
    as $item
): ?><option value="<?= (int) $item["id"] ?>"><?= e(
    $item["project_code"] . " - " . $item["name"],
) ?></option><?php endforeach; ?></select></div><div class="col-md-2"><select id="receipt-contract" class="form-select"><option value="">كل العقود</option><?php foreach (
    $references["contracts"]
    as $item
): ?><option value="<?= (int) $item["id"] ?>"><?= e(
    $item["contract_code"] .
        " - " .
        ($item["contract_number"] ?? $item["title"]),
) ?></option><?php endforeach; ?></select></div><?php endif; ?><div class="col-md-2"><select id="receipt-status" class="form-select"><option value="">كل الحالات</option><option value="draft">مسودة</option><option value="posted">مرحّل</option><option value="cancelled">ملغى</option></select></div><div class="col-md-2"><select id="receipt-method" class="form-select"><option value="">كل طرق الدفع</option><option value="cash">نقدي</option><option value="bank_transfer">تحويل بنكي</option><option value="cheque">شيك</option><option value="other">أخرى</option></select></div><div class="col-md-2"><input id="receipt-from" type="date" class="form-control"></div><div class="col-md-2"><input id="receipt-to" type="date" class="form-control"></div></div></div><div class="table-responsive p-3"><table id="client-receipts-table" class="table w-100"><thead><tr><th>الكود</th><th>رقم السند</th><th>التاريخ</th><th>العميل</th><th>المشروع</th><th>العقد</th><th>المبلغ</th><th>المخصص</th><th>غير المخصص</th><th>طريقة الدفع</th><th>المرجع</th><th>الحالة</th><th>رُحّل بواسطة</th><th>الإجراءات</th></tr></thead></table></div></div><script src="/assets/js/modules/client-receipts.js" defer></script>
