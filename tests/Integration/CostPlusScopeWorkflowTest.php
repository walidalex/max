<?php

declare(strict_types=1);

use App\Core\Auth\Auth;
use App\Core\Database\Database;
use App\Core\Exceptions\BusinessRuleException;
use App\Modules\Subcontracts\Services\SubcontractService;
use App\Modules\Subcontracts\DTOs\SubcontractData;
use App\Modules\SubcontractBoq\Services\SubcontractBoqService;
use App\Modules\SubcontractBoq\DTOs\BoqItemData;
use App\Modules\SubcontractCertificates\Services\SubcontractCertificateService;
use App\Modules\SubcontractCertificates\DTOs\SubcontractCertificateData;
use App\Modules\ProjectCosts\Services\ProjectCostService;
use App\Modules\ProjectCosts\DTOs\ProjectCostData;
use App\Modules\ProjectCosts\Validators\ProjectCostValidator;
use App\Modules\ClientProgressStatements\Services\ClientProgressStatementService;
use App\Modules\ClientProgressStatements\DTOs\ClientProgressStatementData;
use App\Core\View\View;

$db=$app->make(Database::class);
$auth=$app->make(Auth::class);
$previousUser=$auth->user();
$costService=$app->make(ProjectCostService::class);
$statements=$app->make(ClientProgressStatementService::class);
$certificates=$app->make(SubcontractCertificateService::class);
$subcontractService=$app->make(SubcontractService::class);
$boqService=$app->make(SubcontractBoqService::class);
$suffix=bin2hex(random_bytes(5));
$client=$project=$foreignProject=$vendor=$subcontract=$contract=null;
try{
    $user=$db->execute('SELECT id,name,username FROM users WHERE is_active=1 LIMIT 1')->fetch_assoc();
    $code=$db->execute('SELECT c.id,w.id work_section_id FROM cost_codes c JOIN work_sections w ON w.id=c.work_section_id WHERE c.is_active=1 AND w.is_active=1 LIMIT 1')->fetch_assoc();
    $unit=$db->execute("SELECT id FROM units WHERE code='pcs' LIMIT 1")->fetch_assoc();
    $lumpUnit=$db->execute("SELECT id FROM units WHERE code='lump_sum' LIMIT 1")->fetch_assoc();
    $auth->login($user);
    $db->execute("INSERT INTO clients(client_code,client_type,name) VALUES(?,'individual','Scope Client')",['SC-'.$suffix]);
    $client=(int)$db->connection()->insert_id;
    $db->execute("INSERT INTO projects(project_code,name,client_id,project_type,status) VALUES(?,'Scope Project',?,'contracting','active')",['SP-'.$suffix,$client]);
    $project=(int)$db->connection()->insert_id;
    $db->execute("INSERT INTO projects(project_code,name,client_id,project_type,status) VALUES(?,'Foreign Project',?,'contracting','active')",['SF-'.$suffix,$client]);
    $foreignProject=(int)$db->connection()->insert_id;
    $db->execute("INSERT INTO vendors(vendor_code,vendor_type,name) VALUES(?,'subcontractor','Scope Vendor')",['SV-'.$suffix]);
    $vendor=(int)$db->connection()->insert_id;
    $db->execute('INSERT INTO vendor_work_sections(vendor_id,work_section_id) VALUES(?,?)',[$vendor,(int)$code['work_section_id']]);
    $subcontract=$subcontractService->save(new SubcontractData($project,(int)$code['work_section_id'],$vendor,'boq','Scope Contract',null,'2026-01-01',null,null,null),null,[
        new BoqItemData(1,'<b>Quantity scope</b>','quantity',(int)$unit['id'],'100.0000','20.00',null,null),
        new BoqItemData(1,'Lump scope','lump_sum',(int)$lumpUnit['id'],null,null,'1000.00',null),
    ]);
    $items=$db->execute('SELECT i.id FROM subcontract_boq_items i JOIN subcontract_boq_sections bs ON bs.id=i.subcontract_boq_section_id JOIN subcontract_boqs b ON b.id=bs.subcontract_boq_id WHERE b.subcontract_id=? ORDER BY i.id',[$subcontract])->fetch_all(MYSQLI_ASSOC);
    $quantityItem=(int)$items[0]['id'];$lumpItem=(int)$items[1]['id'];
    $boqService->approve($subcontract);
    $db->execute("INSERT INTO client_contracts(contract_code,contract_number,project_id,client_id,pricing_method,contract_date,markup_percentage,title,status) VALUES(?,?,?,?,'cost_plus','2026-01-01','15.0000','Cost Plus Scope','active')",['CC-'.$suffix,'CN-'.$suffix,$project,$client]);
    $contract=(int)$db->connection()->insert_id;
    foreach([[$foreignProject,(int)$code['work_section_id']],[$project,999999]] as [$pid,$section]){
        try{
            $costService->save(new ProjectCostData($pid,'2026-01-05',(int)$code['id'],null,'Invalid mapping',null,'10.00',null,$quantityItem,$section));
            throw new RuntimeException('Cross-project or mismatched scope accepted.');
        }catch(BusinessRuleException){}
    }
    $validator=$app->make(ProjectCostValidator::class);
    $expenseRow=-(int)$code['id'];
    $emptyRows=$statements->scopeRows($contract,'2026-01-15');
    if(array_filter($emptyRows,static fn(array $row):bool=>(int)$row['item_id']<0)!==[])throw new RuntimeException('Approved subcontract populated unused expense classifications.');
    $input=['expense_type'=>'work','project_id'=>(string)$project,'cost_date'=>'2026-01-05','cost_code_id'=>(string)$code['id'],'cost_section_id'=>(string)$code['work_section_id'],'amount'=>'100.00','description'=>'Must not override selected item'];
    $cost=$costService->save($validator->validate($input));
    if(in_array($expenseRow,array_map('intval',array_column($statements->scopeRows($contract,'2026-01-15'),'item_id')),true))throw new RuntimeException('Draft expense introduced a billable expense classification.');
    $stored=$costService->find($cost);
    $codeName=$db->execute('SELECT name FROM cost_codes WHERE id=?',[(int)$code['id']])->fetch_assoc()['name'];
    if($stored['description']!==$codeName||$stored['expense_type']!=='work'||$stored['subcontract_boq_item_id']!==null)throw new RuntimeException('Expense code description or classification incorrect.');
    try{$bad=$input;$bad['cost_section_id']='999999';$costService->save($validator->validate($bad));throw new RuntimeException('Wrong parent cost section accepted.');}catch(BusinessRuleException){}
    $costService->approve($cost);
    $expenseRows=array_values(array_filter($statements->scopeRows($contract,'2026-01-15'),static fn(array $row):bool=>(int)$row['item_id']<0));
    if(count($expenseRows)!==1||(int)$expenseRows[0]['item_id']!==$expenseRow)throw new RuntimeException('Expense scope must contain only the actually used approved code.');
    if(array_filter($statements->scopeRows($contract,'2026-01-04'),static fn(array $row):bool=>(int)$row['item_id']<0)!==[])throw new RuntimeException('Future expense introduced a classification before its cost date.');
    $generalInput=$input;$generalInput['expense_type']='general';$generalInput['amount']='20.00';unset($generalInput['description'],$generalInput['cost_section_id']);
    $general=$costService->save($validator->validate($generalInput));
    $costService->approve($general);
    $certificate=$certificates->save(new SubcontractCertificateData($subcontract,null,null,null,'2026-01-10',null,[$quantityItem=>'25.0000',$lumpItem=>'10.0000'],null));
    $certificates->approve($certificate);
    $draftCertificate=$certificates->save(new SubcontractCertificateData($subcontract,null,null,null,'2026-01-11',null,[$quantityItem=>'60.0000',$lumpItem=>'70.0000'],null));
    $first=$statements->save($contract,new ClientProgressStatementData('2026-01-15',null,null,null,null,[$cost,$general],[]));
    $before=$statements->details($first);$rows=array_column($before['scopeRows'],null,'item_id');
    if(count($rows)<3||$rows[$quantityItem]['cumulative_measure']!=='25.0000'||$rows[$lumpItem]['cumulative_measure']!=='10.0000'||$rows[$expenseRow]['current_cost']!=='100.00'||$rows[$quantityItem]['current_cost']!=='500.00'||$rows[$lumpItem]['current_cost']!=='100.00'||$rows[0]['current_cost']!=='20.00'||$rows[$expenseRow]['work_section_id']!==(int)$code['work_section_id'])throw new RuntimeException('Scope population, approved progress or cost allocation incorrect.');
    $statements->approve($first);
    $approved=$statements->details($first);
    if($approved['statement']['current_cost_amount']!=='720.00'||$approved['statement']['current_statement_amount']!=='828.00')throw new RuntimeException('Cost Plus must include approved subcontract execution and selected expenses with markup.');
    $snapshot=$approved['scopeRows'];
    // Simulate an older approved snapshot containing an unused zero-value expense code.
    $legacyEmptyRow=$rows[$expenseRow];
    $legacyEmptyRow['item_id']=-2147483647;
    foreach(['previous_cost','current_cost','cumulative_cost'] as $field)$legacyEmptyRow[$field]='0.00';
    unset($legacyEmptyRow['group_totals']);
    $snapshot[]=$legacyEmptyRow;
    $db->execute('UPDATE client_progress_statements SET cost_scope_snapshot=? WHERE id=?',[json_encode($snapshot,JSON_THROW_ON_ERROR),$first]);
    $certificate2=$certificates->save(new SubcontractCertificateData($subcontract,null,null,null,'2026-01-20',null,[$quantityItem=>'10.0000',$lumpItem=>'5.0000'],null));
    $certificates->approve($certificate2);
    $db->execute('UPDATE subcontract_boq_items SET description=? WHERE id=?',['Changed source description',$quantityItem]);
    if($statements->details($first)['scopeRows']!==$snapshot)throw new RuntimeException('Approved Cost Plus scope snapshot changed.');
    $secondInput=$input;$secondInput['cost_date']='2026-01-21';$secondInput['amount']='50.00';unset($secondInput['description']);
    $secondCost=$costService->save($validator->validate($secondInput));
    $costService->approve($secondCost);
    $second=$statements->save($contract,new ClientProgressStatementData('2026-01-25',null,null,null,null,[$secondCost],[]));
    $stale=$statements->save($contract,new ClientProgressStatementData('2026-01-25',null,null,null,null,[],[]));
    $rows=array_column($statements->details($second)['scopeRows'],null,'item_id');
    if(isset($rows[-2147483647]))throw new RuntimeException('Unused expense classification from a legacy snapshot carried into a new statement.');
    if($rows[$quantityItem]['previous_measure']!=='25.0000'||$rows[$quantityItem]['current_measure']!=='10.0000'||$rows[$quantityItem]['cumulative_measure']!=='35.0000'||$rows[$expenseRow]['previous_cost']!=='100.00'||$rows[$expenseRow]['current_cost']!=='50.00'||$rows[$expenseRow]['cumulative_cost']!=='150.00')throw new RuntimeException('Previous/current/cumulative scope mismatch.');
    $early=$statements->scopeRows($contract,'2026-01-15',$second);$early=array_column($early,null,'item_id');
    if($early[$quantityItem]['cumulative_measure']!=='25.0000')throw new RuntimeException('Future subcontract progress included.');
    $html=$app->make(View::class)->component('cost-plus-scope-table',['scopeRows'=>$snapshot]);
    if(str_contains($html,'<b>Quantity scope</b>')||!str_contains($html,'&lt;b&gt;Quantity scope&lt;/b&gt;'))throw new RuntimeException('Scope descriptions not escaped.');
    $statements->approve($second);
    if($statements->find($second)['current_statement_amount']!=='345.00')throw new RuntimeException('Second statement did not bill only new execution plus new expenses.');
    try{$statements->approve($stale);throw new RuntimeException('Stale draft billed subcontract execution twice.');}catch(BusinessRuleException){}
}finally{
    if($contract!==null){
        $db->execute('DELETE d FROM client_progress_statement_costs d JOIN client_progress_statements s ON s.id=d.statement_id WHERE s.client_contract_id=?',[$contract]);
        $db->execute('DELETE FROM client_progress_statements WHERE client_contract_id=?',[$contract]);
    }
    if($project!==null)$db->execute('DELETE FROM project_actual_costs WHERE project_id=?',[$project]);
    if($subcontract!==null){
        $db->execute('DELETE i FROM subcontract_progress_items i JOIN subcontract_progress_certificates c ON c.id=i.certificate_id WHERE c.subcontract_id=?',[$subcontract]);
        $db->execute('DELETE FROM subcontract_progress_certificates WHERE subcontract_id=?',[$subcontract]);
        $db->execute('DELETE i FROM subcontract_boq_items i JOIN subcontract_boq_sections bs ON bs.id=i.subcontract_boq_section_id JOIN subcontract_boqs b ON b.id=bs.subcontract_boq_id WHERE b.subcontract_id=?',[$subcontract]);
        $db->execute('DELETE bs FROM subcontract_boq_sections bs JOIN subcontract_boqs b ON b.id=bs.subcontract_boq_id WHERE b.subcontract_id=?',[$subcontract]);
        $db->execute('DELETE FROM subcontract_boqs WHERE subcontract_id=?',[$subcontract]);
        $db->execute('DELETE FROM subcontracts WHERE id=?',[$subcontract]);
    }
    if($vendor!==null){$db->execute('DELETE FROM vendor_work_sections WHERE vendor_id=?',[$vendor]);$db->execute('DELETE FROM vendors WHERE id=?',[$vendor]);}
    if($contract!==null)$db->execute('DELETE FROM client_contracts WHERE id=?',[$contract]);
    if($project!==null)$db->execute('DELETE FROM projects WHERE id=?',[$project]);
    if($foreignProject!==null)$db->execute('DELETE FROM projects WHERE id=?',[$foreignProject]);
    if($client!==null)$db->execute('DELETE FROM clients WHERE id=?',[$client]);
    if($previousUser!==null)$auth->refresh($previousUser);else$auth->logout();
}
