<?php
$methods = ['cash'=>'نقدي','bank_transfer'=>'تحويل بنكي','cheque'=>'شيك','other'=>'أخرى'];
$identity = [
    'رقم السند' => $receipt['receipt_number'] ?: '—',
    'التاريخ' => $receipt['receipt_date'],
    'العميل' => $receipt['client_name_snapshot'],
    'المشروع' => $receipt['project_code_snapshot'].' - '.$receipt['project_name_snapshot'],
    'العقد' => $receipt['contract_code_snapshot'].' - '.($receipt['contract_number_snapshot'] ?? $receipt['contract_title_snapshot'] ?? ''),
    'المبلغ' => $receipt['amount'],
    'طريقة الدفع' => $methods[$receipt['payment_method']] ?? $receipt['payment_method'],
    'المرجع' => $receipt['reference_number'] ?: '—',
    'رُحّل بواسطة' => $receipt['posted_by_name'],
];
?>
<style>@media print{.navbar,.page-header .btn,.footer{display:none!important}.card{border:0!important;box-shadow:none!important}}</style>
<div class="page-header mb-4"><div class="row"><div class="col"><h2 class="page-title">سند قبض عميل</h2><div><?=e($receipt['receipt_code'])?></div></div><div class="col-auto"><button class="btn btn-primary" onclick="window.print()">طباعة</button></div></div></div>
<div class="card"><div class="card-body"><div class="row g-3">
<?php foreach($identity as $label=>$value):?><div class="col-md-4"><div class="text-secondary"><?=$label?></div><strong><?=e((string)$value)?></strong></div><?php endforeach;?>
</div>
<?php if($receipt['notes']):?><hr><?=nl2br(e($receipt['notes']))?><?php endif;?>
<hr><h3>التخصيصات</h3><table class="table"><thead><tr><th>المستخلص</th><th>رقم المستخلص</th><th>التاريخ</th><th>المبلغ المخصص</th></tr></thead><tbody>
<?php foreach($allocations as $allocation):?><tr><td><?=e($allocation['statement_code_snapshot'])?></td><td><?=e($allocation['statement_number_snapshot']??'—')?></td><td><?=e($allocation['statement_date_snapshot'])?></td><td><?=e($allocation['allocated_amount'])?></td></tr><?php endforeach;?>
</tbody><tfoot><tr><th colspan="3">إجمالي المخصص</th><th><?=e($receipt['allocated_amount'])?></th></tr><tr><th colspan="3">غير المخصص</th><th><?=e($receipt['unallocated_amount'])?></th></tr></tfoot></table></div></div>
