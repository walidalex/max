<div class="page-header mb-4"><div class="row align-items-center"><div class="col"><div class="page-pretitle"><?= e(
    $contract["contract_code"],
) ?></div><h2 class="page-title"><?= e(
    $title,
) ?></h2></div><div class="col-auto"><a class="btn" href="<?= $receipt
    ? "/client-receipts/" . (int) $receipt["id"]
    : "/client-contracts/" .
        (int) $contract["id"] .
        "/receipts" ?>">رجوع</a></div></div></div>
<form class="card" method="post" action="<?= $receipt
    ? "/client-receipts/" . (int) $receipt["id"] . "/edit"
    : "/client-contracts/" .
        (int) $contract["id"] .
        "/receipts" ?>"><input type="hidden" name="_token" value="<?= e(
    $csrfToken,
) ?>"><div class="card-body"><div class="row g-3"><div class="col-md-4"><label class="form-label">العقد</label><input class="form-control" value="<?= e(
    $contract["contract_code"] .
        " - " .
        ($contract["contract_number"] ?? $contract["title"]),
) ?>" readonly></div><div class="col-md-4"><label class="form-label">العميل</label><input class="form-control" value="<?= e(
    $contract["client_name"],
) ?>" readonly></div><div class="col-md-4"><label class="form-label">المشروع</label><input class="form-control" value="<?= e(
    $contract["project_code"] . " - " . $contract["project_name"],
) ?>" readonly></div><div class="col-md-4"><label class="form-label">تاريخ السند</label><input type="date" name="receipt_date" class="form-control" required value="<?= e(
    $receipt["receipt_date"] ?? date("Y-m-d"),
) ?>"></div><div class="col-md-4"><label class="form-label">رقم السند التجاري</label><input name="receipt_number" maxlength="100" class="form-control" value="<?= e(
    $receipt["receipt_number"] ?? "",
) ?>"></div><div class="col-md-4"><label class="form-label">المبلغ</label><input name="amount" inputmode="decimal" class="form-control" required value="<?= e(
    $receipt["amount"] ?? "",
) ?>"></div><div class="col-md-4"><label class="form-label">طريقة الدفع</label><select name="payment_method" class="form-select" required><?php foreach (
    [
        "cash" => "نقدي",
        "bank_transfer" => "تحويل بنكي",
        "cheque" => "شيك",
        "other" => "أخرى",
    ]
    as $value => $label
): ?><option value="<?= $value ?>" <?= ($receipt["payment_method"] ??
    "bank_transfer") ===
$value
    ? "selected"
    : "" ?>><?= $label ?></option><?php endforeach; ?></select></div><div class="col-md-8"><label class="form-label">المرجع / رقم التحويل أو الشيك</label><input name="reference_number" maxlength="190" class="form-control" value="<?= e(
    $receipt["reference_number"] ?? "",
) ?>"></div><div class="col-12"><label class="form-label">ملاحظات</label><textarea name="notes" class="form-control" rows="4"><?= e(
    $receipt["notes"] ?? "",
) ?></textarea></div></div></div><div class="card-footer text-end"><button class="btn btn-primary">حفظ المسودة</button></div></form>
