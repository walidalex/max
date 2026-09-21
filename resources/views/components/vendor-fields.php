<?php
$editing = is_array($vendor ?? null);
$value = static fn(string $key): string => (string) (($vendor ?? [])[$key] ?? '');
$fixedVendorType = $fixedVendorType ?? null;
$icons = ['01'=>'road','02'=>'building','03'=>'brick-wall','04'=>'droplet','05'=>'bolt','06'=>'droplet-filled','07'=>'cpu','08'=>'snowflake','09'=>'adjustments-horizontal','10'=>'air-conditioning','13'=>'paint','15'=>'brush','16'=>'building-pavilion','18'=>'tools-kitchen-2','19'=>'pipe'];
?>
<div class="row g-3">
    <?php if ($editing): ?><div class="col-md-4"><label class="form-label">كود المورد</label><input class="form-control" value="<?=e($value('vendor_code'))?>" disabled></div><?php endif; ?>
    <div class="col-md-4"><label class="form-label required">النوع</label><?php if($fixedVendorType==='subcontractor'):?><input type="hidden" class="js-vendor-type" name="vendor_type" value="subcontractor"><input class="form-control" value="مقاول باطن" readonly><?php else:?><select class="form-select js-vendor-type" name="vendor_type" required><?php foreach (['supplier'=>'مورد','subcontractor'=>'مقاول باطن','both'=>'مورد ومقاول باطن'] as $key=>$label): ?><option value="<?=$key?>" <?=$value('vendor_type')===$key?'selected':''?>><?=$label?></option><?php endforeach; ?></select><?php endif;?><div class="invalid-feedback" data-error-for="vendor_type"></div></div>
    <div class="col-md-<?= $editing ? '4' : '8' ?>"><label class="form-label required">الاسم</label><input class="form-control" name="name" maxlength="190" required value="<?=e($value('name'))?>"><div class="invalid-feedback" data-error-for="name"></div></div>
    <div class="col-md-4"><label class="form-label">الرقم الضريبي</label><input class="form-control" name="tax_number" maxlength="80" value="<?=e($value('tax_number'))?>"></div>
    <div class="col-md-4"><label class="form-label">السجل التجاري</label><input class="form-control" name="commercial_registration" maxlength="80" value="<?=e($value('commercial_registration'))?>"></div>
    <div class="col-md-4"><label class="form-label">الهاتف</label><input class="form-control" name="phone" maxlength="30" value="<?=e($value('phone'))?>"><div class="invalid-feedback" data-error-for="phone"></div></div>
    <div class="col-md-4"><label class="form-label">المحمول</label><input class="form-control" name="mobile" maxlength="30" value="<?=e($value('mobile'))?>"><div class="invalid-feedback" data-error-for="mobile"></div></div>
    <div class="col-md-8"><label class="form-label">البريد الإلكتروني</label><input class="form-control" type="email" name="email" maxlength="190" value="<?=e($value('email'))?>"><div class="invalid-feedback" data-error-for="email"></div></div>
    <div class="col-12"><label class="form-label">العنوان</label><textarea class="form-control" name="address" maxlength="500" rows="2"><?=e($value('address'))?></textarea></div>
    <div class="col-12 js-work-sections-group">
        <div class="d-flex align-items-center justify-content-between mb-2"><div><label class="form-label mb-0">مجالات العمل</label><div class="text-secondary small js-work-sections-hint">اختيارية للمورد، ومطلوبة لمقاول الباطن.</div></div><span class="badge bg-blue-lt js-selected-count">0 محدد</span></div>
        <div class="vendor-work-sections"><?php foreach ($workSections as $section): $selected=(int)$section['is_selected']===1; $active=(int)$section['is_active']===1; $icon=$icons[(string)$section['section_code']]??'tool'; ?><?php if(!$active&&$selected):?><input type="hidden" name="work_section_ids[]" value="<?=(int)$section['id']?>"><?php endif;?><label class="vendor-work-section<?=$selected?' is-selected':''?><?=$active?'':' is-inactive'?>"><input class="visually-hidden js-work-section-checkbox" type="checkbox" name="work_section_ids[]" value="<?=(int)$section['id']?>" <?=$selected?'checked':''?> <?=$active?'':'disabled'?>> <span class="vendor-work-section-icon"><i class="ti ti-<?=e($icon)?>"></i></span><span class="vendor-work-section-text"><strong><?=e($section['name'])?></strong><?php if(!$active):?><small>غير نشط</small><?php endif;?></span><i class="ti ti-circle-check vendor-work-section-check"></i></label><?php endforeach; ?></div>
        <div class="invalid-feedback d-block" data-error-for="work_section_ids"></div>
    </div>
    <div class="col-12"><label class="form-label">ملاحظات</label><textarea class="form-control" name="notes" maxlength="5000" rows="3"><?=e($value('notes'))?></textarea></div>
</div>
