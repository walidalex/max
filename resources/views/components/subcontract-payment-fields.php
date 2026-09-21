<?php
$val = static fn (string $key, string $default = ''): string => e((string) ($payment[$key] ?? $default));
$channel = $payment['payment_channel'] ?? 'cash';
$bankMethod = $payment['bank_payment_method'] ?? 'bank_transfer';
?>
<div class="row g-3">
    <div class="col-md-6"><label class="form-label required">تاريخ الدفع</label><input class="form-control" type="date" name="payment_date" required value="<?=$val('payment_date', date('Y-m-d'))?>"></div>
    <div class="col-md-6"><label class="form-label required">المبلغ</label><input class="form-control" type="number" min="0.01" max="9999999999999999.99" step="0.01" name="amount" required value="<?=$val('amount')?>"></div>
    <div class="col-md-6"><label class="form-label required">طريقة الدفع</label><select class="form-select js-payment-channel" name="payment_channel" required><option value="cash" <?=$channel === 'cash' ? 'selected' : ''?>>نقدي</option><option value="bank" <?=$channel === 'bank' ? 'selected' : ''?>>بنكي</option></select></div>
    <div class="col-md-6 js-bank-method"><label class="form-label required">وسيلة العملية البنكية</label><select class="form-select" name="bank_payment_method"><option value="bank_transfer" <?=$bankMethod === 'bank_transfer' ? 'selected' : ''?>>تحويل بنكي</option><option value="cheque" <?=$bankMethod === 'cheque' ? 'selected' : ''?>>شيك</option><option value="instapay" <?=$bankMethod === 'instapay' ? 'selected' : ''?>>إنستاباي</option><option value="other" <?=$bankMethod === 'other' ? 'selected' : ''?>>أخرى</option></select></div>
    <div class="col-12"><label class="form-label required">حساب الدفع</label><select class="form-select js-payment-account" name="payment_account_id" required><option value="">اختر الحساب</option><?php foreach ($accounts as $account): ?><option value="<?=(int) $account['id']?>" data-channel="<?=e($account['channel'])?>" <?=($payment['payment_account_id'] ?? '') == $account['id'] ? 'selected' : ''?>><?=e($account['account_code'].' - '.$account['name_ar'])?></option><?php endforeach; ?></select></div>
    <div class="col-md-6"><label class="form-label js-reference-label">الرقم المرجعي</label><input class="form-control js-reference" maxlength="190" name="reference_number" value="<?=$val('reference_number')?>"></div>
    <div class="col-md-6 js-cheque"><label class="form-label required">رقم الشيك</label><input class="form-control" maxlength="100" name="cheque_number" value="<?=$val('cheque_number')?>"></div>
    <div class="col-md-6 js-cheque"><label class="form-label required">تاريخ الشيك</label><input class="form-control" type="date" name="cheque_date" value="<?=$val('cheque_date')?>"></div>
    <div class="col-md-6 js-cheque"><label class="form-label">تاريخ الاستحقاق</label><input class="form-control" type="date" name="cheque_due_date" value="<?=$val('cheque_due_date')?>"></div>
    <div class="col-12"><label class="form-label">ملاحظات</label><textarea class="form-control" name="notes"><?=$val('notes')?></textarea></div>
</div>
