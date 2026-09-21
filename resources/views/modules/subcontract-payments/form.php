<?php $edit = $payment !== null; ?>
<div class="page-header mb-4"><h2 class="page-title"><?=$edit ? 'تعديل مسودة الدفعة' : 'إضافة دفعة مقاول باطن'?></h2></div>
<div class="alert alert-info">المتاح للدفع: <strong><?=decimal_format((string) $summary['available_payment_amount'])?></strong></div>
<form method="post" class="js-payment-form" action="<?=$edit ? '/subcontract-payments/'.(int) $payment['id'].'/edit' : '/subcontracts/'.(int) $subcontract['id'].'/payments'?>">
    <input type="hidden" name="_token" value="<?=e($csrfToken)?>">
    <div class="card"><div class="card-body"><?php require dirname(__DIR__, 2).'/components/subcontract-payment-fields.php'; ?></div><div class="card-footer text-end"><button class="btn btn-primary">حفظ المسودة</button></div></div>
</form>
<script src="/assets/js/modules/subcontract-payment-form.js" defer></script>
