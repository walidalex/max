<?php
$draft=$certificate['status']==='draft';
$status=['draft'=>'مسودة — غير معتمدة','approved'=>'معتمد','cancelled'=>'ملغي — غير صالح للصرف'][$certificate['status']]??'';
$money=static fn(mixed $number):string=>e(decimal_format((string)($number??'0.00')));
$header=function(string $subtitle) use($company,$companyLogo,$certificate,$status):void{?>
<header class="document-header">
<div class="brand"><?php if($companyLogo):?><img src="<?=e($companyLogo)?>" alt="شعار الشركة"><?php endif;?><div><strong><?=e($company['name'])?></strong><div class="muted"><?=e($company['legal_name']??'')?></div><div class="muted"><?=e($company['phone']??$company['mobile']??'')?></div></div></div>
<div class="document-title"><h1>مستخلص أعمال مقاول باطن</h1><div><?=e($subtitle)?></div><span class="state"><?=e($status)?></span></div>
<div class="document-meta"><div>رقم المستخلص: <strong class="code"><?=e($certificate['certificate_number']??$certificate['certificate_code'])?></strong></div><div>التاريخ: <?=e($certificate['certificate_date'])?></div><div class="muted">الفترة: <?=e($certificate['period_from']??'—')?> إلى <?=e($certificate['period_to']??'—')?></div></div>
</header>
<?php };
?>
<?php if($draft):?><div class="watermark" aria-hidden="true">مسودة</div><?php endif;?>
<section class="sheet">
<?php $header('الملخص المالي وموقف الدفعات');?>
<div class="identity">
<?php foreach(['client_name'=>'العميل','project_name'=>'المشروع','vendor_name'=>'مقاول الباطن','subcontract_code'=>'رقم العقد','work_section_name'=>'مجال العمل','contract_date'=>'تاريخ العقد'] as $key=>$label):?><div><span><?=e($label)?>:</span><strong><?=e($identity[$key]??'—')?></strong></div><?php endforeach;?>
<div><span>قيمة العقد:</span><strong><?=$money($identity['contract_value']??null)?></strong></div><div><span>عنوان العقد:</span><strong><?=e($identity['title']??'—')?></strong></div>
<?php if($certificate['approved_at']):?><div><span>تاريخ الاعتماد:</span><strong><?=e($certificate['approved_at'])?></strong></div><?php endif;?>
</div>
<?php if(!$frozen):?><div class="notice"><?=$draft?'معاينة مسودة: القيم وموقف الدفعات قابلة للتغير حتى اعتماد المستخلص.':'لا توجد لقطة دفعات محفوظة عند اعتماد هذا المستخلص القديم؛ المعروض هو موقف الدفعات الحالي، وقد يشمل دفعات لاحقة للمستخلص.'?></div><?php else:?><div class="muted">موقف الدفعات والأرصدة أدناه محفوظ وقت اعتماد المستخلص.</div><?php endif;?>
<h2>الأعمال المنفذة</h2>
<table><thead><tr><th>البيان</th><th>السابق</th><th>الحالي</th><th>الإجمالي التراكمي</th></tr></thead><tbody><tr><td>قيمة الأعمال المنفذة</td><td class="numeric"><?=$money($totals['previous_value']??null)?></td><td class="numeric"><?=$money($totals['current_value']??null)?></td><td class="numeric"><?=$money($totals['cumulative_value']??null)?></td></tr></tbody></table>
<h2>الدفعات المصروفة — مرحلة فقط</h2>
<table><thead><tr><th>التاريخ</th><th>رقم الدفعة</th><th>طريقة الدفع</th><th>المرجع</th><th>المبلغ</th><th>ملاحظات</th></tr></thead><tbody>
<?php $methods=['cash'=>'نقدي','bank_transfer'=>'تحويل بنكي','cheque'=>'شيك','other'=>'أخرى / إنستاباي'];foreach($payments as $payment):?><tr><td class="numeric"><?=e($payment['payment_date'])?></td><td class="numeric"><?=e($payment['payment_code'])?></td><td><?=e($methods[$payment['payment_method']]??'—')?></td><td><?=e($payment['reference_number']??'—')?></td><td class="numeric"><?=$money($payment['amount'])?></td><td><?=e($payment['notes']??'')?></td></tr><?php endforeach;?>
<?php if($payments===[]):?><tr><td colspan="6" class="empty">لا توجد دفعات مرحلة.</td></tr><?php endif;?>
<tr class="total"><th colspan="4">إجمالي الدفعات المصروفة</th><td class="numeric"><?=$money($totals['paid_total']??null)?></td><td></td></tr></tbody></table>
<div class="balance"><strong>الرصيد المتبقي = الأعمال التراكمية − الدفعات المصروفة</strong><strong class="code"><?=$money($totals['balance']??null)?></strong></div>
<div class="muted">هذا المستخلص بيان أعمال؛ لا يُعد سند صرف أو إثبات سداد. الرصيد السالب يعني أن الدفعات تتجاوز قيمة أعمال هذا المستخلص.</div>
<?php if(!empty($certificate['notes'])):?><div class="notes"><strong>ملاحظات المستخلص:</strong><br><?=e($certificate['notes'])?></div><?php endif;?>
<div class="signatures"><?php foreach(['مقاول الباطن','المكتب الفني','مدير المشروع','الإدارة المالية'] as $label):?><div class="signature"><?=e($label)?><div class="muted">الاسم والتوقيع</div></div><?php endforeach;?></div>
<footer class="section-footer"><span><?=e($company['name'])?></span><span><?=e($certificate['certificate_code'])?> — ملخص المستخلص</span></footer>
</section>
<section class="sheet">
<?php $header('تفاصيل الأعمال المنفذة');?>
<div class="identity"><div><span>المشروع:</span><?=e($identity['project_name']??'—')?></div><div><span>المقاول:</span><?=e($identity['vendor_name']??'—')?></div><div><span>العقد:</span><?=e($identity['subcontract_code']??'—')?></div></div>
<?php if($items!==[]):?>
<table class="details"><thead><tr><th rowspan="2" class="index">م</th><th rowspan="2" class="description">وصف الأعمال</th><th rowspan="2">الوحدة</th><th colspan="3">أساس العقد</th><th colspan="3">التنفيذ</th><th rowspan="2">الإنجاز الإجمالي %</th><th colspan="3">قيمة الأعمال</th></tr><tr><th>الكمية</th><th>سعر الوحدة</th><th>القيمة</th><th>السابق</th><th>الحالي</th><th>التراكمي</th><th>السابق</th><th>الحالي</th><th>التراكمي</th></tr></thead><tbody>
<?php foreach($items as $index=>$item):$lump=$item['pricing_type_snapshot']==='lump_sum';?><tr><td class="numeric"><?=$index+1?></td><td><?=e($item['description_snapshot'])?></td><td><?=e($lump?'مقطوعية':($item['unit_symbol_snapshot']??$item['unit_name_snapshot']??'—'))?></td><td class="numeric"><?=$lump?'—':e(decimal_format((string)$item['contract_quantity'],4))?></td><td class="numeric"><?=$lump?'—':$money($item['unit_rate'])?></td><td class="numeric"><?=$money($item['contract_amount'])?></td><?php foreach(['print_previous','print_current','print_cumulative'] as $key):?><td class="numeric"><?=e(decimal_format((string)$item[$key],4))?><?=$lump?'%':''?></td><?php endforeach;?><td class="numeric"><?=e(decimal_format((string)$item['print_progress'],2))?>%</td><td class="numeric"><?=$money($item['print_previous_amount'])?></td><td class="numeric"><?=$money($draft?$item['live_current_amount']:$item['current_amount'])?></td><td class="numeric"><?=$money($draft?$item['live_cumulative_amount']:$item['cumulative_amount'])?></td></tr><?php endforeach;?>
<tr class="total"><th colspan="10">إجمالي الأعمال</th><td class="numeric"><?=$money($totals['previous_value']??null)?></td><td class="numeric"><?=$money($totals['current_value']??null)?></td><td class="numeric"><?=$money($totals['cumulative_value']??null)?></td></tr>
</tbody></table>
<?php else:?><table><thead><tr><th>البيان</th><th>السابق %</th><th>الحالي %</th><th>التراكمي %</th><th>القيمة الحالية</th><th>القيمة التراكمية</th></tr></thead><tbody><tr><td><?=e($identity['title']??'أعمال العقد المقطوع')?></td><td class="numeric"><?=e($certificate['previous_progress_percentage']??'—')?></td><td class="numeric"><?=e($certificate['current_progress_percentage']??'—')?></td><td class="numeric"><?=e($certificate['cumulative_progress_percentage']??'—')?></td><td class="numeric"><?=$money($totals['current_value']??null)?></td><td class="numeric"><?=$money($totals['cumulative_value']??null)?></td></tr></tbody></table><?php endif;?>
<div class="muted">التنفيذ للبند الكمي بالوحدة التعاقدية، وللبند المقطوعي بالنسبة المئوية. الحالي يمثل أعمال هذا المستخلص فقط.</div>
<div class="signatures"><?php foreach(['مقاول الباطن','المكتب الفني','مدير المشروع','الإدارة المالية'] as $label):?><div class="signature"><?=e($label)?></div><?php endforeach;?></div>
<footer class="section-footer"><span><?=e($company['name'])?></span><span><?=e($certificate['certificate_code'])?> — تفاصيل الأعمال</span></footer>
</section>
