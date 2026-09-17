<div class="page-header mb-4"><div class="row"><div class="col"><div class="page-pretitle"><?= e(
    $receipt["receipt_code"],
) ?></div><h2 class="page-title">تخصيص سند القبض</h2></div><div class="col-auto"><a class="btn" href="/client-receipts/<?= (int) $receipt[
    "id"
] ?>">رجوع</a></div></div></div>
<div class="alert alert-info">المبلغ غير المخصص: <strong><?= e(
    $receipt["unallocated_amount"],
) ?></strong>. يمكن تخصيص السند لمستخلص واحد أو عدة مستخلصات في عملية واحدة.</div>
<form class="card js-confirm-form" method="post" action="/client-receipts/<?= (int) $receipt[
    "id"
] ?>/allocate" data-confirm="سيتم حفظ التخصيصات كسجلات مالية غير قابلة للتعديل أو الحذف."><input type="hidden" name="_token" value="<?= e(
    $csrfToken,
) ?>"><div class="table-responsive"><table class="table card-table"><thead><tr><th>المستخلص</th><th>الرقم</th><th>التسلسل</th><th>التاريخ</th><th>قيمة المستخلص</th><th>المخصص سابقاً</th><th>المستحق</th><th>التخصيص الحالي</th><th>ملاحظات</th></tr></thead><tbody><?php
if (
    !$statements
): ?><tr><td colspan="9" class="text-center text-secondary">لا توجد مستخلصات معتمدة ذات رصيد مستحق.</td></tr><?php endif;
foreach ($statements as $index => $statement): ?><tr><td><?= e(
    $statement["statement_code"],
) ?><input type="hidden" name="allocations[<?= $index ?>][statement_id]" value="<?= (int) $statement[
    "id"
] ?>"></td><td><?= e($statement["statement_number"] ?? "—") ?></td><td><?= e(
    (string) $statement["statement_sequence"],
) ?></td><td><?= e($statement["statement_date"]) ?></td><td><?= e(
    $statement["current_statement_amount"],
) ?></td><td><?= e($statement["allocated_amount"]) ?></td><td><?= e(
    $statement["outstanding_amount"],
) ?></td><td><input name="allocations[<?= $index ?>][allocated_amount]" inputmode="decimal" class="form-control" placeholder="0.00"></td><td><input name="allocations[<?= $index ?>][notes]" maxlength="5000" class="form-control"></td></tr><?php endforeach;
?></tbody></table></div><?php if (
    $statements
): ?><div class="card-footer text-end"><button class="btn btn-primary">حفظ التخصيصات</button></div><?php endif; ?></form>
