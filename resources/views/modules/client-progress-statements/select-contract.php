<?php
declare(strict_types=1);
$projects = [];
foreach ($contracts as $item) $projects[(int)$item['project_id']] = $item;
$value = static fn(string $key): string => is_scalar($old[$key] ?? '') ? (string)($old[$key] ?? '') : '';
?>
<div class="page-header mb-4"><div class="row align-items-center g-3"><div class="col"><div class="page-pretitle">مستخلصات العملاء</div><h2 class="page-title">إنشاء مستخلص عميل</h2><div class="text-secondary mt-1">اختر المشروع والعقد لفتح نموذج المستخلص.</div></div><div class="col-auto"><a class="btn" href="/client-progress-statements">العودة إلى المستخلصات</a></div></div></div>
<?php if ($errors): ?><div class="alert alert-danger" role="alert"><ul class="mb-0"><?php foreach ($errors as $messages): foreach ($messages as $message): ?><li><?=e($message)?></li><?php endforeach; endforeach; ?></ul></div><?php endif; ?>
<?php if (!$contracts): ?>
<div class="card"><div class="card-body text-center py-5"><i class="ti ti-file-invoice fs-1 text-secondary"></i><h3 class="mt-3">لا توجد عقود مؤهلة لإنشاء مستخلص</h3><p class="text-secondary mb-0">يلزم عقد تكلفة فعلية + نسبة أو جدول كميات، بحالة نشط أو معلق أو مكتمل. عقود جدول الكميات تحتاج إلى جدول معتمد.</p></div></div>
<?php else: ?>
<form class="card" id="cps-contract-selection" method="get" action="/client-progress-statements/create">
    <div class="card-body"><div class="row g-3">
        <div class="col-md-6"><label class="form-label required" for="cps-create-project">المشروع</label><select id="cps-create-project" name="project_id" class="form-select js-tom-select" required><option value="">اختر المشروع</option><?php foreach ($projects as $id => $item): ?><option value="<?=$id?>" <?=$value('project_id') === (string)$id ? 'selected' : ''?>><?=e($item['project_code'].' - '.$item['project_name'])?></option><?php endforeach; ?></select></div>
        <div class="col-md-6"><label class="form-label required" for="cps-create-contract">العقد</label><select id="cps-create-contract" name="contract_id" class="form-select js-tom-select" required><option value="">اختر العقد</option><?php foreach ($contracts as $item): ?><option value="<?=(int)$item['id']?>" data-project-id="<?=(int)$item['project_id']?>" data-client-name="<?=e($item['client_name'])?>" data-pricing-label="<?=$item['pricing_method'] === 'boq' ? 'جدول كميات' : 'تكلفة فعلية + نسبة'?>" <?=$value('contract_id') === (string)$item['id'] ? 'selected' : ''?>><?=e($item['contract_code'].' - '.($item['contract_number'] ?? '').' - '.$item['title'])?></option><?php endforeach; ?></select></div>
        <div class="col-md-6"><label class="form-label" for="cps-create-client">العميل</label><input id="cps-create-client" class="form-control" readonly></div>
        <div class="col-md-6"><label class="form-label" for="cps-create-pricing">طريقة التسعير</label><input id="cps-create-pricing" class="form-control" readonly></div>
    </div><div class="form-hint mt-3">تظهر العقود المؤهلة فقط. الانتقال للنموذج لا ينشئ مستخلصًا؛ يُحفظ عند الضغط على «حفظ المسودة».</div></div>
    <div class="card-footer d-flex gap-2"><button class="btn btn-primary" type="submit">متابعة لإنشاء المستخلص <i class="ti ti-arrow-left me-2"></i></button><a class="btn" href="/client-progress-statements">إلغاء</a></div>
</form>
<script src="/assets/js/modules/client-progress-statement-selection.js" defer></script>
<?php endif; ?>
