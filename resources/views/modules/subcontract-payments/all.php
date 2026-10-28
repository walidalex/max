<?php
$vendors = $projects = [];
foreach ($contracts as $contract) {
    $vendors[(int)$contract['vendor_id']] = $contract['vendor_name'];
    $projects[(int)$contract['project_id']] = $contract['project_name'];
}
asort($vendors);
asort($projects);
?>
<div class="page-header mb-4"><div class="row align-items-center"><div class="col"><div class="page-pretitle">مقاولو الباطن</div><h2 class="page-title">دفعات مقاولي الباطن</h2></div><?php if($canCreate):?><div class="col-auto"><button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#select-payment-contract"><i class="ti ti-plus ms-2"></i>إضافة دفعة</button></div><?php endif;?></div></div>
<div class="card" id="payments-card" data-endpoint="/api/subcontract-payments">
<div class="card-body border-bottom"><div class="row g-3">
<div class="col-md-4"><label class="form-label" for="pay-vendor">المقاول</label><select class="form-select js-tom-select" id="pay-vendor"><option value="">كل المقاولين</option><?php foreach($vendors as $id=>$name):?><option value="<?=$id?>"><?=e($name)?></option><?php endforeach;?></select></div>
<div class="col-md-4"><label class="form-label" for="pay-project">المشروع</label><select class="form-select js-tom-select" id="pay-project"><option value="">كل المشاريع</option><?php foreach($projects as $id=>$name):?><option value="<?=$id?>"><?=e($name)?></option><?php endforeach;?></select></div>
<div class="col-md-4"><label class="form-label" for="pay-contract">العقد</label><select class="form-select js-tom-select" id="pay-contract"><option value="">كل العقود</option><?php foreach($contracts as $contract):?><option value="<?=(int)$contract['id']?>"><?=e($contract['subcontract_code'].' - '.$contract['title'])?></option><?php endforeach;?></select></div>
<div class="col-md-3"><label class="form-label" for="pay-from">من تاريخ</label><input id="pay-from" type="date" class="form-control"></div>
<div class="col-md-3"><label class="form-label" for="pay-to">إلى تاريخ</label><input id="pay-to" type="date" class="form-control"></div>
<div class="col-md-3"><label class="form-label" for="pay-status">الحالة</label><select id="pay-status" class="form-select js-tom-select"><option value="">كل الحالات</option><option value="draft">مسودة</option><option value="posted">مرحلة</option><option value="cancelled">ملغاة</option></select></div>
<div class="col-md-3"><label class="form-label" for="pay-method">طريقة الدفع</label><select id="pay-method" class="form-select js-tom-select"><option value="">كل الطرق</option><option value="cash">نقدي</option><option value="bank_transfer">تحويل بنكي</option><option value="cheque">شيك</option><option value="other">أخرى / إنستاباي</option></select></div>
</div></div>
<div class="table-responsive p-3"><table id="payments-table" class="table table-vcenter w-100"><thead><tr><th>الكود</th><th>التاريخ</th><th>المقاول</th><th>المشروع</th><th>المبلغ</th><th>الطريقة</th><th>المرجع</th><th>الإنجاز %</th><th>القيمة المعتمدة</th><th>الحالة</th><th>المنشئ</th><th>المرحل</th><th>الإجراءات</th><th>العقد</th></tr></thead></table></div></div>
<?php if($canCreate):?>
<div class="modal modal-blur fade" id="select-payment-contract" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><form class="modal-content" id="select-payment-contract-form"><div class="modal-header"><h3 class="modal-title">إضافة دفعة مقاول باطن</h3><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button></div><div class="modal-body"><label class="form-label required" for="new-payment-contract">العقد</label><select class="form-select js-tom-select" id="new-payment-contract" required><option value="">اختر العقد</option><?php foreach($contracts as $contract):if(!in_array($contract['status'],['active','suspended','completed'],true))continue;?><option value="<?=(int)$contract['id']?>"><?=e($contract['subcontract_code'].' - '.$contract['vendor_name'].' - '.$contract['project_name'])?></option><?php endforeach;?></select><div class="form-hint">اختر العقد للانتقال إلى بيانات الدفعة وعرض المبلغ المتاح للدفع.</div></div><div class="modal-footer"><button class="btn btn-link" type="button" data-bs-dismiss="modal">إلغاء</button><button class="btn btn-primary" type="submit">متابعة <i class="ti ti-arrow-left me-2"></i></button></div></form></div></div>
<?php endif;?>
<script src="/assets/js/modules/subcontract-payments.js" defer></script>
