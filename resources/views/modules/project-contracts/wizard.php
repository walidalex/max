<?php
declare(strict_types=1);

$projectOld = is_array($old['project'] ?? null) ? $old['project'] : [];
$contractOld = is_array($old['contract'] ?? null) ? $old['contract'] : [];
$p = static fn(string $key): string => is_scalar($projectOld[$key] ?? '') ? (string) ($projectOld[$key] ?? '') : '';
$c = static fn(string $key): string => is_scalar($contractOld[$key] ?? '') ? (string) ($contractOld[$key] ?? '') : '';
$types = ['interior_design'=>'تصميم داخلي','fit_out'=>'تجهيز وتشطيب','contracting'=>'مقاولات','renovation'=>'تجديد','maintenance'=>'صيانة','other'=>'أخرى'];
?>
<div class="page-header mb-4"><div class="row align-items-center g-3"><div class="col"><div class="page-pretitle">المشاريع وعقود العملاء</div><h2 class="page-title">إضافة مشروع وعقد عميل</h2><div class="text-secondary mt-1">أكمل البيانات خطوة بخطوة ثم راجعها قبل الحفظ.</div></div><div class="col-auto"><a class="btn" href="/project-contracts">العودة إلى الدليل</a></div></div></div>
<?php if ($errors !== []): ?><div class="alert alert-danger" role="alert"><div><h3 class="alert-title">راجع البيانات التالية</h3><ul class="mb-0"><?php foreach ($errors as $messages): foreach ($messages as $message): ?><li><?=e($message)?></li><?php endforeach; endforeach; ?></ul></div></div><?php endif; ?>
<noscript><div class="alert alert-warning">يلزم تفعيل جافاسكربت لاستخدام معالج الإضافة.</div></noscript>
<form id="project-contract-wizard" class="card" method="post" action="/project-contracts">
    <input type="hidden" name="_token" value="<?=e($csrfToken)?>">
    <div class="card-header"><ol class="steps steps-green w-100 mb-0" aria-label="خطوات الإضافة">
        <?php foreach ([1=>'العميل والمشروع',2=>'الموقع والمواعيد',3=>'العقد والتسعير',4=>'المراجعة والحفظ'] as $index => $label): ?><li class="step-item px-1" data-step-indicator="<?=$index?>"><button type="button" class="btn btn-link p-0 text-wrap lh-sm" data-go-step="<?=$index?>"><?=$label?></button></li><?php endforeach; ?>
    </ol></div>
    <div class="card-body">
        <section data-wizard-step="1" aria-labelledby="wizard-title-1">
            <h3 id="wizard-title-1">العميل وبيانات المشروع</h3><div class="row g-3">
                <div class="col-md-6"><label class="form-label required" for="client_id">العميل</label><div class="input-group"><select class="form-select js-tom-select" id="client_id" name="project[client_id]" required><option value="">اختر العميل</option><?php foreach ($clients as $item): if (!$item['is_active']) continue; ?><option value="<?=(int)$item['id']?>" <?=$p('client_id') === (string)$item['id'] ? 'selected' : ''?>><?=e($item['client_code'].' - '.$item['name'])?></option><?php endforeach; ?></select><?php if ($canCreateClient): ?><button class="btn btn-outline-primary" id="add-project-client" type="button" title="إضافة عميل جديد" aria-label="إضافة عميل جديد"><i class="ti ti-user-plus"></i></button><?php endif; ?></div></div>
                <div class="col-md-6"><label class="form-label required" for="wizard-project-name">اسم المشروع</label><input class="form-control" id="wizard-project-name" name="project[name]" maxlength="190" required value="<?=e($p('name'))?>"></div>
                <div class="col-md-4"><label class="form-label" for="primary_contact_id">جهة الاتصال الأساسية</label><select id="primary_contact_id" class="form-select js-tom-select" name="project[primary_contact_id]" data-selected="<?=e($p('primary_contact_id'))?>"><option value="">بدون</option></select></div>
                <div class="col-md-4"><label class="form-label" for="wizard-manager">مدير المشروع</label><select id="wizard-manager" class="form-select js-tom-select" name="project[project_manager_id]"><option value="">بدون</option><?php foreach ($managers as $item): ?><option value="<?=(int)$item['id']?>" <?=$p('project_manager_id') === (string)$item['id'] ? 'selected' : ''?>><?=e($item['name'])?></option><?php endforeach; ?></select></div>
                <div class="col-md-4"><label class="form-label required" for="wizard-type">نوع المشروع</label><select id="wizard-type" class="form-select" name="project[project_type]" required><?php foreach ($types as $key => $label): ?><option value="<?=$key?>" <?=$p('project_type') === $key ? 'selected' : ''?>><?=$label?></option><?php endforeach; ?></select></div>
            </div>
        </section>
        <section data-wizard-step="2" aria-labelledby="wizard-title-2" hidden>
            <h3 id="wizard-title-2">الموقع والمواعيد</h3><div class="row g-3">
                <div class="col-md-6"><label class="form-label" for="wizard-governorate">المحافظة</label><select id="wizard-governorate" class="form-select js-tom-select" name="project[governorate]"><option value="">اختر المحافظة</option><?php foreach (egypt_governorates() as $code => $label): ?><option value="<?=e($code)?>" <?=$p('governorate') === $code ? 'selected' : ''?>><?=e($label)?></option><?php endforeach; ?></select></div>
                <div class="col-md-6"><label class="form-label" for="wizard-area">المنطقة</label><input id="wizard-area" class="form-control" name="project[area]" maxlength="120" value="<?=e($p('area'))?>"></div>
                <div class="col-12"><label class="form-label" for="wizard-address">عنوان الموقع</label><textarea id="wizard-address" class="form-control" name="project[site_address]" maxlength="500" rows="2"><?=e($p('site_address'))?></textarea></div>
                <?php foreach (['start_date'=>'تاريخ البدء','expected_end_date'=>'تاريخ الانتهاء المتوقع','actual_end_date'=>'تاريخ الانتهاء الفعلي'] as $key => $label): ?><div class="col-md-4"><label class="form-label" for="project-<?=$key?>"><?=$label?></label><input id="project-<?=$key?>" class="form-control" type="date" name="project[<?=$key?>]" value="<?=e($p($key))?>"></div><?php endforeach; ?>
                <?php foreach (['description'=>'الوصف','notes'=>'ملاحظات المشروع'] as $key => $label): ?><div class="col-12"><label class="form-label" for="project-<?=$key?>"><?=$label?></label><textarea id="project-<?=$key?>" class="form-control" name="project[<?=$key?>]" maxlength="5000" rows="3"><?=e($p($key))?></textarea></div><?php endforeach; ?>
            </div>
        </section>
        <section data-wizard-step="3" aria-labelledby="wizard-title-3" hidden>
            <h3 id="wizard-title-3">بيانات العقد والتسعير</h3><div class="alert alert-info">يُحفظ العقد كمسودة. يُنشأ رقم العقد تلقائيًا، ويمكن استكمال جدول الكميات واعتماد العقد من صفحة تفاصيله.</div><div class="row g-3">
                <div class="col-md-6"><label class="form-label">العميل</label><input id="wizard-contract-client" class="form-control" readonly></div>
                <div class="col-md-6"><label class="form-label" for="wizard-contract-contact">جهة اتصال العقد</label><select id="wizard-contract-contact" class="form-select js-tom-select" name="contract[client_contact_id]" data-selected="<?=e($c('client_contact_id'))?>"><option value="">بدون</option></select></div>
                <div class="col-md-6"><label class="form-label required" for="wizard-contract-title">عنوان العقد</label><input id="wizard-contract-title" class="form-control" name="contract[title]" maxlength="190" required value="<?=e($c('title'))?>"></div>
                <div class="col-md-6"><label class="form-label required" for="wizard-contract-date">تاريخ العقد</label><input id="wizard-contract-date" type="date" class="form-control" name="contract[contract_date]" required value="<?=e($c('contract_date'))?>"></div>
                <div class="col-md-4"><label class="form-label required" for="wizard-pricing">طريقة التسعير</label><select id="wizard-pricing" class="form-select" name="contract[pricing_method]" required><?php foreach (['cost_plus'=>'تكلفة فعلية + نسبة','boq'=>'جدول كميات','lump_sum'=>'مقطوعية'] as $key => $label): ?><option value="<?=$key?>" <?=$c('pricing_method') === $key ? 'selected' : ''?>><?=$label?></option><?php endforeach; ?></select></div>
                <div class="col-md-4"><label class="form-label" for="wizard-contract-value">قيمة العقد</label><input id="wizard-contract-value" class="form-control" type="number" min="0" step="0.01" name="contract[contract_value]" value="<?=e($c('contract_value'))?>"></div>
                <div class="col-md-4" id="wizard-markup-field"><label class="form-label required" for="wizard-markup">نسبة الزيادة %</label><input id="wizard-markup" class="form-control" type="number" min="0.0001" step="0.0001" name="contract[markup_percentage]" value="<?=e($c('markup_percentage'))?>"></div>
                <?php foreach (['start_date'=>'تاريخ بدء العقد','expected_end_date'=>'تاريخ الانتهاء المتوقع للعقد'] as $key => $label): ?><div class="col-md-6"><label class="form-label" for="contract-<?=$key?>"><?=$label?></label><input id="contract-<?=$key?>" class="form-control" type="date" name="contract[<?=$key?>]" value="<?=e($c($key))?>"></div><?php endforeach; ?>
                <?php foreach (['description'=>'وصف العقد','terms'=>'الشروط','notes'=>'ملاحظات العقد'] as $key => $label): ?><div class="col-12"><label class="form-label" for="contract-<?=$key?>"><?=$label?></label><textarea id="contract-<?=$key?>" class="form-control" name="contract[<?=$key?>]" maxlength="<?=$key === 'terms' ? '10000' : '5000'?>" rows="3"><?=e($c($key))?></textarea></div><?php endforeach; ?>
            </div>
        </section>
        <section data-wizard-step="4" aria-labelledby="wizard-title-4" hidden><h3 id="wizard-title-4">المراجعة والحفظ</h3><p class="text-secondary">راجع البيانات قبل الحفظ. يمكنك الرجوع لأي خطوة لتعديلها.</p><div id="wizard-summary" class="row g-3"></div><div class="alert alert-info mt-4" id="wizard-save-note"></div></section>
    </div>
    <div class="card-footer d-flex flex-wrap align-items-center gap-2"><a class="btn btn-link" href="/project-contracts">إلغاء</a><span class="text-secondary me-auto" id="wizard-progress" aria-live="polite"></span><button class="btn" type="button" id="wizard-previous">السابق</button><button class="btn btn-primary" type="button" id="wizard-next">التالي <i class="ti ti-arrow-left me-2"></i></button><button class="btn btn-primary" type="submit" id="wizard-save" hidden>حفظ المشروع والعقد</button></div>
</form>
<?php if ($canCreateClient): ?><?=$this->component('project-client-modal', ['csrfToken'=>$csrfToken])?><?php endif; ?>
<script src="/assets/js/modules/project-form.js" defer></script>
<script src="/assets/js/modules/project-contract-wizard.js" defer></script>
