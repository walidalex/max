<?php
$editing = is_array($vendor ?? null);
$value = static fn(string $key): string => (string) (($vendor ?? [])[$key] ?? '');
$fixedVendorType = $fixedVendorType ?? null;
$canManageWorkSections = (bool) ($canManageWorkSections ?? false);
$sectionIcon = static function (string $name): string {
    return match (true) {
        str_contains($name,'مدني') => 'road', str_contains($name,'مباني') => 'building',
        str_contains($name,'محارة') => 'wall', str_contains($name,'عزل') => 'droplet-off',
        str_contains($name,'كهرباء') => 'bolt', str_contains($name,'سباكة') => 'bath',
        str_contains($name,'سيراميك') => 'grid-dots', str_contains($name,'جبس') => 'layout-navbar',
        str_contains($name,'تشطيبات') => 'adjustments-horizontal', str_contains($name,'تكييف') => 'air-conditioning',
        str_contains($name,'شفاط') => 'propeller',
        str_contains($name,'أبواب') => 'door', str_contains($name,'دهان') => 'paint',
        str_contains($name,'رخام') => 'columns',
        str_contains($name,'جي آر سي'), str_contains(strtoupper($name),'GRC') => 'building-arch',
        str_contains($name,'مصروفات') => 'receipt-2', default => 'building-construction',
    };
};
?>
<div class="row g-3">
    <?php if ($editing): ?><div class="col-md-4"><label class="form-label">كود المورد</label><input class="form-control" value="<?=e($value('vendor_code'))?>" disabled></div><?php endif; ?>
    <div class="col-md-4"><label class="form-label required">النوع</label><?php if($fixedVendorType==='subcontractor'):?><input type="hidden" class="js-vendor-type" name="vendor_type" value="subcontractor"><input class="form-control" value="مقاول باطن" readonly><?php else:?><select class="form-select js-vendor-type" name="vendor_type" required><?php foreach (['supplier'=>'مورد','subcontractor'=>'مقاول باطن','both'=>'مورد ومقاول باطن'] as $key=>$label): ?><option value="<?=$key?>" <?=$value('vendor_type')===$key?'selected':''?>><?=$label?></option><?php endforeach; ?></select><?php endif;?><div class="invalid-feedback" data-error-for="vendor_type"></div></div>
    <div class="col-md-<?= $editing ? '4' : '8' ?>"><label class="form-label required">الاسم</label><input class="form-control" name="name" maxlength="190" required value="<?=e($value('name'))?>"><div class="invalid-feedback" data-error-for="name"></div></div>
    <div class="col-md-4"><label class="form-label">الرقم الضريبي</label><input class="form-control" name="tax_number" maxlength="80" value="<?=e($value('tax_number'))?>"></div>
    <div class="col-md-4"><label class="form-label">السجل التجاري</label><input class="form-control" name="commercial_registration" maxlength="80" value="<?=e($value('commercial_registration'))?>"></div>
    <div class="col-md-4"><label class="form-label">الهاتف</label><input class="form-control" name="phone" maxlength="30" value="<?=e($value('phone'))?>"><div class="invalid-feedback" data-error-for="phone"></div></div>
    <div class="col-md-4"><label class="form-label">المحمول</label><input class="form-control" name="mobile" maxlength="30" value="<?=e($value('mobile'))?>"><div class="invalid-feedback" data-error-for="mobile"></div></div>
    <div class="col-md-4"><label class="form-label">البريد الإلكتروني</label><input class="form-control" type="email" name="email" maxlength="190" value="<?=e($value('email'))?>"><div class="invalid-feedback" data-error-for="email"></div></div>
    <div class="col-md-4"><label class="form-label">اسم البنك</label><select class="form-select js-tom-select" name="bank_name" data-placeholder="اختر البنك" data-hide-empty-option="true"><option value=""></option><?php foreach(egypt_banks() as $code=>$label):?><option value="<?=e($code)?>" <?=$value('bank_name')===$code?'selected':''?>><?=e($label)?></option><?php endforeach;?></select><div class="invalid-feedback" data-error-for="bank_name"></div></div>
    <div class="col-md-4"><label class="form-label">الحساب البنكي</label><input class="form-control" name="bank_account" maxlength="190" value="<?=e($value('bank_account'))?>" placeholder="رقم الحساب أو IBAN"></div>
    <div class="col-md-4"><label class="form-label">رقم InstaPay</label><input class="form-control" name="instapay_number" maxlength="100" value="<?=e($value('instapay_number'))?>" placeholder="رقم الهاتف أو عنوان InstaPay"></div>
    <div class="col-md-4"><label class="form-label">المحافظة</label><select class="form-select js-tom-select" name="governorate" data-placeholder="اختر المحافظة" data-hide-empty-option="true"><option value=""></option><?php foreach(egypt_governorates() as $code=>$label):?><option value="<?=e($code)?>" <?=$value('governorate')===$code?'selected':''?>><?=e($label)?></option><?php endforeach;?></select><div class="invalid-feedback" data-error-for="governorate"></div></div>
    <div class="col-12"><label class="form-label">العنوان</label><textarea class="form-control" name="address" maxlength="500" rows="2"><?=e($value('address'))?></textarea></div>
    <div class="col-12 js-work-sections-group">
        <div class="d-flex align-items-center justify-content-between gap-2 mb-2"><div><label class="form-label mb-0"><i class="ti ti-building-construction ms-1 text-primary"></i>مجالات العمل</label><div class="text-secondary small js-work-sections-hint">اختيارية للمورد، ومطلوبة لمقاول الباطن.</div></div><div class="d-flex align-items-center gap-2"><?php if($canManageWorkSections):?><button class="btn btn-sm btn-outline-primary js-show-work-section-create" type="button"><i class="ti ti-building-construction ms-1"></i>إضافة مجال عمل</button><?php endif;?><span class="badge bg-blue-lt js-selected-count">0 محدد</span></div></div>
        <?php if($canManageWorkSections):?><div class="card card-body bg-light mb-3 d-none js-work-section-create"><div class="row g-2 align-items-end"><div class="col-md-9"><label class="form-label fw-bold">اسم مجال العمل</label><input class="form-control form-control-sm" data-work-section-field="name" maxlength="190" placeholder="مثال: أعمال الجبس بورد"></div><div class="col-md-3"><button class="btn btn-primary btn-sm w-100 js-save-work-section" type="button"><i class="ti ti-plus ms-1"></i>إضافة واختيار</button></div><div class="col-12"><div class="text-danger small d-none js-work-section-error"></div></div></div></div><?php endif;?>
        <div class="vendor-work-sections"><?php foreach ($workSections as $section): $selected=(int)$section['is_selected']===1; $active=(int)$section['is_active']===1; $icon=$sectionIcon((string)$section['name']); ?><?php if(!$active&&$selected):?><input type="hidden" name="work_section_ids[]" value="<?=(int)$section['id']?>"><?php endif;?><label class="vendor-work-section<?=$selected?' is-selected':''?><?=$active?'':' is-inactive'?>"><input class="visually-hidden js-work-section-checkbox" type="checkbox" name="work_section_ids[]" value="<?=(int)$section['id']?>" <?=$selected?'checked':''?> <?=$active?'':'disabled'?>> <span class="vendor-work-section-icon"><i class="ti ti-<?=e($icon)?>"></i></span><span class="vendor-work-section-text"><strong><?=e($section['name'])?></strong><?php if(!$active):?><small>غير نشط</small><?php endif;?></span><i class="ti ti-circle-check vendor-work-section-check"></i></label><?php endforeach; ?></div>
        <div class="invalid-feedback d-block" data-error-for="work_section_ids"></div>
    </div>
</div>
