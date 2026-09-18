<div class='page-header mb-3'><h2 class='page-title'>إعدادات الترحيل المحاسبي</h2></div>
<div class='card table-responsive mb-4'>
 <div class='card-header'><h3 class='card-title'>الحسابات الرئيسية</h3></div>
 <table class='table'><thead><tr><th>المفتاح</th><th>الحساب الحالي</th><th>تغيير الحساب</th></tr></thead><tbody>
 <?php foreach($mappings as$m):?><tr><td><code><?=e($m['mapping_key'])?></code></td><td><?=e($m['account_code'].' - '.$m['name_ar'])?></td><td><form class='d-flex gap-2' method='post' action='/accounting/setup'><input type='hidden' name='_token' value='<?=e($csrfToken)?>'><input type='hidden' name='mapping_key' value='<?=e($m['mapping_key'])?>'><select class='form-select' name='account_id'><?php foreach($accounts as$a):?><option value='<?=e($a['id'])?>' <?=$a['id']===$m['account_id']?'selected':''?>><?=e($a['account_code'].' - '.$a['name_ar'])?></option><?php endforeach;?></select><button class='btn btn-primary'>حفظ</button></form></td></tr><?php endforeach;?>
 </tbody></table>
</div>
<div class='card table-responsive'>
 <div class='card-header'><div><h3 class='card-title'>ربط أكواد التكلفة بحسابات المصروفات</h3><div class='text-secondary'>يُستخدم هذا الربط الصريح عند ترحيل مصروفات العهد.</div></div></div>
 <table class='table'><thead><tr><th>قسم العمل</th><th>كود التكلفة</th><th>حساب المصروف</th></tr></thead><tbody>
 <?php foreach($costCodeMappings as$m):?><tr><td><?=e($m['section_code'].' - '.$m['section_name'])?></td><td><?=e($m['cost_code'].' - '.$m['cost_code_name'])?></td><td><form class='d-flex gap-2' method='post' action='/accounting/setup/cost-codes'><input type='hidden' name='_token' value='<?=e($csrfToken)?>'><input type='hidden' name='cost_code_id' value='<?=e($m['cost_code_id'])?>'><select class='form-select' name='account_id' required><option value=''>اختر حساب المصروف</option><?php foreach($expenseAccounts as$a):?><option value='<?=e($a['id'])?>' <?=(string)$a['id']===(string)$m['account_id']?'selected':''?>><?=e($a['account_code'].' - '.$a['name_ar'])?></option><?php endforeach;?></select><button class='btn btn-primary'>حفظ</button></form></td></tr><?php endforeach;?>
 </tbody></table>
</div>
