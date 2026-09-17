<?php

declare(strict_types=1);

use App\Core\Database\Database;

$app = require dirname(__DIR__) . '/bootstrap/app.php';
$environment = (string) env('APP_ENV', '');
$databaseName = (string) env('DB_DATABASE', '');
if ($environment !== 'testing' || !str_ends_with(strtolower($databaseName), '_test')) {
    fwrite(STDERR, "رفض التشغيل: بيانات DEMO مسموحة فقط مع APP_ENV=testing وقاعدة تنتهي بـ _test." . PHP_EOL);
    exit(2);
}

/** @var Database $database */
$database = $app->make(Database::class);
$db = $database->connection();
$reset = in_array('--reset', $argv, true);

$one = static function (string $sql, array $params = []) use ($database): ?array {
    $result = $database->execute($sql, $params);
    $row = $result instanceof mysqli_result ? $result->fetch_assoc() : null;
    return is_array($row) ? $row : null;
};
$id = static function (string $table, string $column, string $value) use ($one): int {
    return (int) ($one("SELECT id FROM {$table} WHERE {$column}=? LIMIT 1", [$value])['id'] ?? 0);
};

$database->transaction(function () use ($database, $one, $id, $reset): void {
    $userId = (int) ($one('SELECT id FROM users WHERE is_active=1 ORDER BY id LIMIT 1')['id'] ?? 0);
    if ($userId < 1) throw new RuntimeException('يجب وجود مستخدم نشط قبل إنشاء بيانات DEMO.');

    $demoContracts = "SELECT id FROM client_contracts WHERE contract_code LIKE 'DEMO-%'";
    $demoProjects = "SELECT id FROM projects WHERE project_code LIKE 'DEMO-%'";
    $demoSubcontracts = "SELECT id FROM subcontracts WHERE subcontract_code LIKE 'DEMO-%'";
    $database->execute("DELETE FROM client_receipt_allocations WHERE receipt_id IN(SELECT id FROM client_receipts WHERE receipt_code LIKE 'DEMO-%')");
    $database->execute("DELETE FROM client_receipts WHERE receipt_code LIKE 'DEMO-%'");
    $database->execute("DELETE FROM client_progress_statement_costs WHERE statement_id IN(SELECT id FROM client_progress_statements WHERE statement_code LIKE 'DEMO-%')");
    $database->execute("DELETE FROM client_progress_statement_variations WHERE statement_id IN(SELECT id FROM client_progress_statements WHERE statement_code LIKE 'DEMO-%')");
    $database->execute("DELETE FROM client_progress_statements WHERE statement_code LIKE 'DEMO-%'");
    $database->execute("DELETE FROM project_actual_costs WHERE cost_entry_code LIKE 'DEMO-%'");
    $database->execute("DELETE FROM subcontract_payments WHERE payment_code LIKE 'DEMO-%'");
    $database->execute("DELETE FROM subcontract_progress_items WHERE certificate_id IN(SELECT id FROM subcontract_progress_certificates WHERE certificate_code LIKE 'DEMO-%')");
    $database->execute("DELETE FROM subcontract_progress_certificates WHERE certificate_code LIKE 'DEMO-%'");
    $database->execute("DELETE FROM subcontract_boq_items WHERE subcontract_boq_section_id IN(SELECT s.id FROM subcontract_boq_sections s JOIN subcontract_boqs b ON b.id=s.subcontract_boq_id WHERE b.subcontract_id IN({$demoSubcontracts}))");
    $database->execute("DELETE FROM subcontract_boq_sections WHERE subcontract_boq_id IN(SELECT id FROM subcontract_boqs WHERE subcontract_id IN({$demoSubcontracts}))");
    $database->execute("DELETE FROM subcontract_boqs WHERE subcontract_id IN({$demoSubcontracts})");
    $database->execute("DELETE FROM subcontracts WHERE subcontract_code LIKE 'DEMO-%'");
    $database->execute("DELETE FROM contract_boq_items WHERE contract_boq_section_id IN(SELECT s.id FROM contract_boq_sections s JOIN contract_boqs b ON b.id=s.contract_boq_id WHERE b.client_contract_id IN({$demoContracts}))");
    $database->execute("DELETE FROM contract_boq_sections WHERE contract_boq_id IN(SELECT id FROM contract_boqs WHERE client_contract_id IN({$demoContracts}))");
    $database->execute("DELETE FROM contract_boqs WHERE client_contract_id IN({$demoContracts})");
    $database->execute("DELETE FROM contract_variations WHERE variation_code LIKE 'DEMO-%'");
    $database->execute("DELETE FROM client_contracts WHERE contract_code LIKE 'DEMO-%'");
    $database->execute("DELETE FROM projects WHERE project_code LIKE 'DEMO-%'");
    $database->execute("DELETE FROM client_contacts WHERE client_id IN(SELECT id FROM clients WHERE client_code LIKE 'DEMO-%')");
    $database->execute("DELETE FROM clients WHERE client_code LIKE 'DEMO-%'");
    $database->execute("DELETE FROM vendor_contacts WHERE vendor_id IN(SELECT id FROM vendors WHERE vendor_code LIKE 'DEMO-%')");
    $database->execute("DELETE FROM vendors WHERE vendor_code LIKE 'DEMO-%'");
    if ($reset) return;

    foreach ([
        ['DEMO-CL-001','company','شركة النخبة للتطوير',1],
        ['DEMO-CL-002','individual','أحمد محمود السيد',1],
        ['DEMO-CL-003','company','شركة الأفق للاستثمار',0],
    ] as $client) $database->execute('INSERT INTO clients(client_code,client_type,name,company_name,mobile,email,address,notes,is_active)VALUES(?,?,?,?,?,?,?,?,?)', [$client[0],$client[1],$client[2],$client[1]==='company'?$client[2]:null,'01000000001',strtolower($client[0]).'@example.test','القاهرة','بيانات تجريبية DEMO',$client[3]]);
    $client1=$id('clients','client_code','DEMO-CL-001');$client2=$id('clients','client_code','DEMO-CL-002');$client3=$id('clients','client_code','DEMO-CL-003');
    $database->execute("INSERT INTO client_contacts(client_id,name,job_title,mobile,email,is_primary,notes)VALUES(?,?,?,?,?,1,'DEMO')",[$client1,'محمد علي','مدير المشاريع','01010000001','demo.client1@example.test']);$contact1=(int)$database->connection()->insert_id;
    $database->execute("INSERT INTO client_contacts(client_id,name,job_title,mobile,email,is_primary,notes)VALUES(?,?,?,?,?,1,'DEMO')",[$client2,'أحمد محمود','المالك','01010000002','demo.client2@example.test']);$contact2=(int)$database->connection()->insert_id;

    foreach ([['DEMO-VN-001','supplier','شركة مواد البناء الحديثة',1],['DEMO-VN-002','subcontractor','مؤسسة الإتقان للمقاولات',1],['DEMO-VN-003','both','شركة الحلول المتكاملة',1],['DEMO-VN-004','supplier','مورد غير نشط للتجربة',0]] as $vendor) $database->execute("INSERT INTO vendors(vendor_code,vendor_type,name,mobile,email,address,notes,is_active)VALUES(?,?,?,?,?,'القاهرة','DEMO',?)",[$vendor[0],$vendor[1],$vendor[2],'01100000001',strtolower($vendor[0]).'@example.test',$vendor[3]]);
    $supplier=$id('vendors','vendor_code','DEMO-VN-001');$subVendor=$id('vendors','vendor_code','DEMO-VN-002');$bothVendor=$id('vendors','vendor_code','DEMO-VN-003');
    foreach([[$supplier,'مسؤول مبيعات المورد'],[$subVendor,'مدير مقاول الباطن'],[$bothVendor,'مسؤول الحسابات']]as$v)$database->execute("INSERT INTO vendor_contacts(vendor_id,name,mobile,is_primary,notes)VALUES(?,?,'01110000001',1,'DEMO')",$v);

    $projects=[['DEMO-PRJ-001','مشروع فيلا التجمع',$client1,$contact1,'fit_out','active'],['DEMO-PRJ-002','مشروع مقر إداري',$client2,$contact2,'interior_design','active'],['DEMO-PRJ-003','مشروع تجديد الفندق',$client1,$contact1,'renovation','on_hold'],['DEMO-PRJ-004','مشروع مكتمل',$client2,$contact2,'contracting','completed'],['DEMO-PRJ-005','مشروع ملغي',$client3,null,'maintenance','cancelled']];
    foreach($projects as$p)$database->execute("INSERT INTO projects(project_code,name,client_id,primary_contact_id,project_manager_id,project_type,status,start_date,expected_end_date,site_address,city,description,notes)VALUES(?,?,?,?,?,?,?,'2026-01-01','2026-12-31','موقع تجريبي','القاهرة','مشروع DEMO لاختبارات القبول','DEMO')",[$p[0],$p[1],$p[2],$p[3],$userId,$p[4],$p[5]]);
    $project1=$id('projects','project_code','DEMO-PRJ-001');$project2=$id('projects','project_code','DEMO-PRJ-002');$project3=$id('projects','project_code','DEMO-PRJ-003');$project4=$id('projects','project_code','DEMO-PRJ-004');

    $contracts=[['DEMO-CTR-2026-0001','DEMO-C-001',$project1,$client1,$contact1,'cost_plus',null,'15.0000','عقد تكلفة فعلية','active'],['DEMO-CTR-2026-0002','DEMO-C-002',$project2,$client2,$contact2,'boq','254000.00',null,'عقد جدول كميات','active'],['DEMO-CTR-2026-0003','DEMO-C-003',$project3,$client1,$contact1,'lump_sum','500000.00',null,'عقد مقطوعية معلق','suspended'],['DEMO-CTR-2026-0004','DEMO-C-004',$project4,$client2,$contact2,'lump_sum','100000.00',null,'عقد مكتمل','completed']];
    foreach($contracts as$c)$database->execute("INSERT INTO client_contracts(contract_code,contract_number,project_id,client_id,client_contact_id,pricing_method,contract_date,start_date,expected_end_date,contract_value,markup_percentage,title,description,notes,status)VALUES(?,?,?,?,?,?,'2026-01-01','2026-01-01','2026-12-31',?,?,?,'عقد DEMO','DEMO',?)",[$c[0],$c[1],$c[2],$c[3],$c[4],$c[5],$c[6],$c[7],$c[8],$c[9]]);
    $contract1=$id('client_contracts','contract_code','DEMO-CTR-2026-0001');$contract2=$id('client_contracts','contract_code','DEMO-CTR-2026-0002');

    foreach([['DEMO-VAR-001',$contract1,'DEMO-VO-01','additional_work','أعمال إضافية معتمدة','increase','25000.00','approved'],['DEMO-VAR-002',$contract1,'DEMO-VO-02','deduction','خصم تجريبي','decrease','5000.00','draft'],['DEMO-VAR-003',$contract1,null,'addendum','ملحق غير مالي','none',null,'approved']]as$v)$database->execute("INSERT INTO contract_variations(variation_code,client_contract_id,variation_number,variation_type,title,variation_date,amount,amount_effect,status,approved_at,approved_by,notes)VALUES(?,?,?,?,?,'2026-03-01',?,?,?,IF(?='approved',NOW(),NULL),IF(?='approved',?,NULL),'DEMO')",[$v[0],$v[1],$v[2],$v[3],$v[4],$v[6],$v[5],$v[7],$v[7],$v[7],$userId]);

    $unit=$one('SELECT id,name_ar AS name,symbol_ar AS symbol FROM units WHERE is_active=1 ORDER BY id LIMIT 1');$section=$one('SELECT id,section_code,name FROM work_sections WHERE is_active=1 ORDER BY id LIMIT 1');$costCode=$one('SELECT id,cost_code,name FROM cost_codes WHERE is_active=1 ORDER BY id LIMIT 1');
    if(!$unit||!$section||!$costCode)throw new RuntimeException('بيانات هيكل التكاليف الأساسية غير متاحة.');
    $database->execute("INSERT INTO contract_boqs(client_contract_id,status,approved_at,approved_by,notes)VALUES(?,'approved',NOW(),?,'DEMO')",[$contract2,$userId]);$clientBoq=(int)$database->connection()->insert_id;
    $database->execute("INSERT INTO contract_boq_sections(contract_boq_id,work_section_id,section_code,title,sort_order)VALUES(?,?,?,?,1)",[$clientBoq,$section['id'],$section['section_code'],$section['name']]);$clientSection=(int)$database->connection()->insert_id;
    $database->execute("INSERT INTO contract_boq_items(contract_boq_section_id,cost_code_id,item_code,description,unit_id,unit_name,unit_symbol,quantity,unit_rate,sort_order,notes)VALUES(?,?,?,?,?,?,?,?,?,1,'DEMO')",[$clientSection,$costCode['id'],$costCode['cost_code'],'أعمال جدول كميات تجريبية',$unit['id'],$unit['name'],$unit['symbol'],'100.0000','2540.00']);

    $database->execute("INSERT INTO subcontracts(subcontract_code,subcontract_number,project_id,vendor_id,pricing_method,title,contract_date,start_date,expected_end_date,contract_value,status,notes)VALUES('DEMO-SUB-2026-0001','DEMO-SUB-01',?,?,'boq','مقاولة أعمال تشطيبات','2026-01-10','2026-01-15','2026-10-31','254000.00','active','DEMO')",[$project1,$subVendor]);$subBoq=(int)$database->connection()->insert_id;
    $database->execute("INSERT INTO subcontracts(subcontract_code,subcontract_number,project_id,vendor_id,pricing_method,title,contract_date,start_date,expected_end_date,contract_value,status,notes)VALUES('DEMO-SUB-2026-0002','DEMO-SUB-02',?,?,'lump_sum','مقاولة أعمال كهرباء','2026-01-10','2026-01-15','2026-10-31','100000.00','active','DEMO')",[$project2,$bothVendor]);$subLump=(int)$database->connection()->insert_id;
    $database->execute("INSERT INTO subcontract_boqs(subcontract_id,status,approved_at,approved_by,notes)VALUES(?,'approved',NOW(),?,'DEMO')",[$subBoq,$userId]);$sboq=(int)$database->connection()->insert_id;
    $database->execute("INSERT INTO subcontract_boq_sections(subcontract_boq_id,work_section_id,section_code,title,sort_order)VALUES(?,?,?,?,1)",[$sboq,$section['id'],$section['section_code'],'أعمال التشطيبات']);$ssection=(int)$database->connection()->insert_id;
    $database->execute("INSERT INTO subcontract_boq_items(subcontract_boq_section_id,cost_code_id,item_code,description,unit_id,unit_name,unit_symbol,quantity,unit_rate,sort_order,notes)VALUES(?,?,?,?,?,?,?,?,?,1,'DEMO')",[$ssection,$costCode['id'],'DEMO-ITEM-01','أعمال أبواب ونوافذ وتشطيبات',$unit['id'],$unit['name'],$unit['symbol'],'100.0000','2540.00']);$sitem=(int)$database->connection()->insert_id;

    $database->execute("INSERT INTO subcontract_progress_certificates(certificate_code,certificate_number,subcontract_id,certificate_date,status,previous_earned_value,current_earned_value,cumulative_earned_value,approved_at,approved_by,notes)VALUES('DEMO-SPC-2026-0001','DEMO-PC-01',?,'2026-04-01','approved','0.00','190500.00','190500.00',NOW(),?,'DEMO')",[$subBoq,$userId]);$certBoq=(int)$database->connection()->insert_id;
    $database->execute("INSERT INTO subcontract_progress_items(certificate_id,subcontract_boq_item_id,description_snapshot,unit_name_snapshot,unit_symbol_snapshot,contract_quantity,unit_rate,previous_quantity,current_quantity,cumulative_quantity,current_amount,cumulative_amount,sort_order)VALUES(?,?,?,?,?,'100.0000','2540.00','0.0000','75.0000','75.0000','190500.00','190500.00',1)",[$certBoq,$sitem,'أعمال أبواب ونوافذ وتشطيبات',$unit['name'],$unit['symbol']]);
    $database->execute("INSERT INTO subcontract_progress_certificates(certificate_code,certificate_number,subcontract_id,certificate_date,status,previous_progress_percentage,current_progress_percentage,cumulative_progress_percentage,previous_earned_value,current_earned_value,cumulative_earned_value,approved_at,approved_by,notes)VALUES('DEMO-SPC-2026-0002','DEMO-PC-02',?,'2026-03-01','approved','0.0000','20.0000','20.0000','0.00','20000.00','20000.00',NOW(),?,'DEMO')",[$subLump,$userId]);
    $database->execute("INSERT INTO subcontract_progress_certificates(certificate_code,subcontract_id,certificate_date,status,current_progress_percentage,notes)VALUES('DEMO-SPC-2026-0003',?,'2026-05-01','draft','5.0000','DEMO draft')",[$subLump]);

    $database->execute("INSERT INTO subcontract_payments(payment_code,subcontract_id,vendor_id,project_id,payment_date,amount,payment_method,reference_number,approved_progress_percentage_snapshot,approved_earned_value_snapshot,previous_payments_snapshot,available_payment_snapshot,status,created_by,posted_at,posted_by,notes)VALUES('DEMO-SPAY-2026-0001',?,?,?,'2026-04-05','180000.00','bank_transfer','DEMO-TR-01','75.0000','190500.00','0.00','190500.00','posted',?,NOW(),?,'DEMO')",[$subBoq,$subVendor,$project1,$userId,$userId]);
    $database->execute("INSERT INTO subcontract_payments(payment_code,subcontract_id,vendor_id,project_id,payment_date,amount,payment_method,reference_number,approved_progress_percentage_snapshot,approved_earned_value_snapshot,previous_payments_snapshot,available_payment_snapshot,status,created_by,posted_at,posted_by,notes)VALUES('DEMO-SPAY-2026-0002',?,?,?,'2026-03-05','15000.00','cash','DEMO-CASH-01','20.0000','20000.00','0.00','20000.00','posted',?,NOW(),?,'DEMO')",[$subLump,$bothVendor,$project2,$userId,$userId]);
    $database->execute("INSERT INTO subcontract_payments(payment_code,subcontract_id,vendor_id,project_id,payment_date,amount,payment_method,status,created_by,notes)VALUES('DEMO-SPAY-2026-0003',?,?,?,'2026-05-01','5000.00','cheque','draft',?,'DEMO draft')",[$subLump,$bothVendor,$project2,$userId]);

    $database->execute("INSERT INTO project_actual_costs(cost_entry_code,project_id,cost_code_id,vendor_id,cost_date,description,reference_number,amount,source_type,work_section_code_snapshot,work_section_name_snapshot,cost_code_snapshot,cost_code_name_snapshot,status,created_by,approved_at,approved_by,notes)VALUES('DEMO-COST-2026-0001',?,?,?,'2026-02-01','توريد مواد تجريبية','DEMO-INV-01','80000.00','manual',?,?,?,?,'approved',?,NOW(),?,'DEMO')",[$project1,$costCode['id'],$supplier,$section['section_code'],$section['name'],$costCode['cost_code'],$costCode['name'],$userId,$userId]);$actualCost=(int)$database->connection()->insert_id;
    $database->execute("INSERT INTO project_actual_costs(cost_entry_code,project_id,cost_code_id,cost_date,description,amount,status,source_type,created_by,notes)VALUES('DEMO-COST-2026-0002',?,?,'2026-03-01','تكلفة مسودة','5000.00','draft','manual',?,'DEMO')",[$project1,$costCode['id'],$userId]);

    $database->execute("INSERT INTO client_progress_statements(statement_code,statement_number,client_contract_id,project_id,client_id,pricing_method_snapshot,client_name_snapshot,project_code_snapshot,project_name_snapshot,contract_code_snapshot,contract_number_snapshot,contract_title_snapshot,statement_date,statement_sequence,markup_percentage_snapshot,previous_cost_amount,current_cost_amount,cumulative_cost_amount,previous_markup_amount,current_markup_amount,cumulative_markup_amount,previous_variation_amount,current_variation_amount,cumulative_variation_amount,previous_statement_amount,current_statement_amount,cumulative_statement_amount,status,created_by,approved_at,approved_by,notes)VALUES('DEMO-CPS-2026-0001','DEMO-PS-01',?,?,?,'cost_plus','شركة النخبة للتطوير','DEMO-PRJ-001','مشروع فيلا التجمع','DEMO-CTR-2026-0001','DEMO-C-001','عقد تكلفة فعلية','2026-04-15',1,'15.0000','0.00','80000.00','80000.00','0.00','12000.00','12000.00','0.00','0.00','0.00','0.00','92000.00','92000.00','approved',?,NOW(),?,'DEMO')",[$contract1,$project1,$client1,$userId,$userId]);$statement=(int)$database->connection()->insert_id;
    $database->execute("INSERT INTO client_progress_statement_costs(statement_id,project_actual_cost_id,is_committed,cost_date_snapshot,description_snapshot,reference_number_snapshot,work_section_code_snapshot,work_section_name_snapshot,cost_code_snapshot,cost_code_name_snapshot,vendor_code_snapshot,vendor_name_snapshot,amount_snapshot,source_type_snapshot,sort_order)VALUES(?,?,1,'2026-02-01','توريد مواد تجريبية','DEMO-INV-01',?,?,?,?,?,'شركة مواد البناء الحديثة','80000.00','manual',1)",[$statement,$actualCost,$section['section_code'],$section['name'],$costCode['cost_code'],$costCode['name'],'DEMO-VN-001']);

    $database->execute("INSERT INTO client_receipts(receipt_code,receipt_number,client_contract_id,project_id,client_id,receipt_date,amount,payment_method,reference_number,status,client_name_snapshot,project_code_snapshot,project_name_snapshot,contract_code_snapshot,contract_number_snapshot,contract_title_snapshot,created_by,posted_at,posted_by,notes)VALUES('DEMO-CRCT-2026-0001','DEMO-R-01',?,?,?,'2026-04-20','100000.00','bank_transfer','DEMO-BANK-01','posted','شركة النخبة للتطوير','DEMO-PRJ-001','مشروع فيلا التجمع','DEMO-CTR-2026-0001','DEMO-C-001','عقد تكلفة فعلية',?,NOW(),?,'DEMO')",[$contract1,$project1,$client1,$userId,$userId]);$receipt=(int)$database->connection()->insert_id;
    $database->execute("INSERT INTO client_receipt_allocations(receipt_id,client_progress_statement_id,allocated_amount,statement_code_snapshot,statement_number_snapshot,statement_sequence_snapshot,statement_date_snapshot,statement_amount_snapshot,allocated_at,allocated_by,notes)VALUES(?,?,'70000.00','DEMO-CPS-2026-0001','DEMO-PS-01',1,'2026-04-15','92000.00',NOW(),?,'DEMO')",[$receipt,$statement,$userId]);
    $database->execute("INSERT INTO client_receipts(receipt_code,client_contract_id,project_id,client_id,receipt_date,amount,payment_method,status,created_by,notes)VALUES('DEMO-CRCT-2026-0002',?,?,?,'2026-05-01','25000.00','cheque','draft',?,'DEMO draft')",[$contract1,$project1,$client1,$userId]);
});

echo $reset ? "تم حذف بيانات DEMO فقط بنجاح." . PHP_EOL : "تم إنشاء بيانات DEMO الشاملة بنجاح في {$databaseName}." . PHP_EOL;
