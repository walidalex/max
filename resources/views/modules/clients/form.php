<?php $editing=is_array($client); $value=static fn(string $key):string=>(string)($client[$key]??''); ?>
<div class="page-header mb-4"><div class="row align-items-center"><div class="col"><div class="page-pretitle">العملاء</div><h2 class="page-title"><?=e($title)?></h2></div></div></div>
<form class="card" method="post" action="<?=$editing?'/clients/'.(int)$client['id'].'/edit':'/clients'?>">
    <div class="card-body"><input type="hidden" name="_token" value="<?=e($csrfToken)?>"><div class="row g-3">
        <?php if($editing):?><div class="col-md-4"><label class="form-label fw-bold">كود العميل</label><input class="form-control" value="<?=e($value('client_code'))?>" disabled></div><?php endif;?>
        <div class="col-md-4"><label class="form-label fw-bold required" for="client_type">نوع العميل</label><select class="form-select" id="client_type" name="client_type" required><option value="individual" <?=$value('client_type')!=='company'?'selected':''?>>فرد</option><option value="company" <?=$value('client_type')==='company'?'selected':''?>>شركة</option></select></div>
        <div class="col-md-4 js-individual-field"><label class="form-label fw-bold">اسم العميل</label><input class="form-control" name="name" maxlength="190" value="<?=e($value('name'))?>"></div>
        <div class="col-md-4 js-individual-field"><label class="form-label fw-bold">رقم البطاقة الشخصية</label><input class="form-control" name="national_id" maxlength="30" value="<?=e($value('national_id'))?>"></div>
        <div class="col-md-4 js-company-field"><label class="form-label fw-bold">اسم الشركة</label><input class="form-control" name="company_name" maxlength="190" value="<?=e($value('company_name'))?>"></div>
        <div class="col-md-4 js-company-field"><label class="form-label fw-bold">الرقم الضريبي</label><input class="form-control" name="tax_number" maxlength="80" value="<?=e($value('tax_number'))?>"></div>
        <div class="col-md-4 js-company-field"><label class="form-label fw-bold">السجل التجاري</label><input class="form-control" name="commercial_registration" maxlength="80" value="<?=e($value('commercial_registration'))?>"></div>
        <div class="col-md-4"><label class="form-label fw-bold">الهاتف</label><input class="form-control" name="phone" maxlength="30" value="<?=e($value('phone'))?>"></div>
        <div class="col-md-4"><label class="form-label fw-bold">المحمول</label><input class="form-control" name="mobile" maxlength="30" value="<?=e($value('mobile'))?>"></div>
        <div class="col-md-4"><label class="form-label fw-bold">البريد الإلكتروني</label><input class="form-control" type="email" name="email" maxlength="190" value="<?=e($value('email'))?>"></div>
        <div class="col-12"><label class="form-label fw-bold">العنوان</label><textarea class="form-control" name="address" maxlength="500" rows="2"><?=e($value('address'))?></textarea></div>
        <div class="col-12"><label class="form-label fw-bold">ملاحظات</label><textarea class="form-control" name="notes" maxlength="5000" rows="3"><?=e($value('notes'))?></textarea></div>
    </div></div>
    <div class="card-footer text-end"><a class="btn btn-link" href="<?=$editing?'/clients/'.(int)$client['id']:'/clients'?>">إلغاء</a><button class="btn btn-primary" type="submit"><i class="ti ti-device-floppy ms-2"></i>حفظ</button></div>
</form>
<script src="/assets/js/modules/client-form.js" defer></script>
