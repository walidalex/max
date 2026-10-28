<?php

declare(strict_types=1);

use App\Core\Auth\Auth;
use App\Core\Database\Database;
use App\Core\Exceptions\BusinessRuleException;
use App\Modules\ClientProgressStatements\DTOs\ClientProgressStatementData;
use App\Modules\ClientProgressStatements\Services\ClientProgressStatementService;
use App\Modules\ProjectCosts\DTOs\ProjectCostData;
use App\Modules\ProjectCosts\Services\ProjectCostService;
use App\Modules\SubcontractBoq\DTOs\BoqItemData;
use App\Modules\SubcontractBoq\Services\SubcontractBoqService;
use App\Modules\SubcontractCertificates\DTOs\SubcontractCertificateData;
use App\Modules\SubcontractCertificates\Services\SubcontractCertificateService;
use App\Modules\Subcontracts\DTOs\SubcontractData;
use App\Modules\Subcontracts\Services\SubcontractService;

(static function () use ($app): void {
    $db=$app->make(Database::class);
    $auth=$app->make(Auth::class);
    $previousUser=$auth->user();
    $statements=$app->make(ClientProgressStatementService::class);
    $certificates=$app->make(SubcontractCertificateService::class);
    $costs=$app->make(ProjectCostService::class);
    $subcontracts=$app->make(SubcontractService::class);
    $suffix=bin2hex(random_bytes(5));
    $client=$vendor=null;
    $projectIds=$contractIds=$subcontractIds=[];
    $assert=static function (bool $condition,string $message): void { if(!$condition)throw new RuntimeException($message); };
    try {
        $user=$db->execute('SELECT id,name,username FROM users WHERE is_active=1 LIMIT 1')->fetch_assoc();
        $auth->refresh($user);
        $code=$db->execute('SELECT c.id,c.work_section_id FROM cost_codes c JOIN work_sections w ON w.id=c.work_section_id WHERE c.is_active=1 AND w.is_active=1 LIMIT 1')->fetch_assoc();
        $unit=$db->execute("SELECT id FROM units WHERE code='pcs' AND is_active=1")->fetch_assoc();
        $db->execute("INSERT INTO clients(client_code,client_type,name) VALUES(?,'individual','Earned Client')",['EC-'.$suffix]);$client=(int)$db->connection()->insert_id;
        $db->execute("INSERT INTO vendors(vendor_code,vendor_type,name) VALUES(?,'subcontractor','Earned Vendor')",['EV-'.$suffix]);$vendor=(int)$db->connection()->insert_id;
        $db->execute('INSERT INTO vendor_work_sections(vendor_id,work_section_id) VALUES(?,?)',[$vendor,(int)$code['work_section_id']]);
        foreach(['boq','lump_sum'] as $method){
            $db->execute("INSERT INTO projects(project_code,name,client_id,project_type,status) VALUES(?,'Earned Project',?,'contracting','active')",['EP-'.$suffix.'-'.$method,$client]);
            $project=(int)$db->connection()->insert_id;$projectIds[]=$project;
            $db->execute("INSERT INTO client_contracts(contract_code,contract_number,project_id,client_id,pricing_method,contract_date,markup_percentage,title,status) VALUES(?,?,?,?,'cost_plus','2026-01-01','15.0000','Earned Cost Plus','active')",['EC-'.$suffix.'-'.$method,'EN-'.$suffix.'-'.$method,$project,$client]);
            $contract=(int)$db->connection()->insert_id;$contractIds[]=$contract;
            $subcontract=$subcontracts->save(new SubcontractData($project,(int)$code['work_section_id'],$vendor,$method,'Master Bathroom Plumbing',null,'2026-01-01',null,null,null),null,$method==='boq'?[new BoqItemData(1,'Master Bathroom Plumbing','quantity',(int)$unit['id'],'1.0000','5000.00',null,null)]:[]);
            $subcontractIds[]=$subcontract;
            $item=null;
            if($method==='boq'){
                $app->make(SubcontractBoqService::class)->approve($subcontract);
                $item=(int)$db->execute('SELECT i.id FROM subcontract_boq_items i JOIN subcontract_boq_sections s ON s.id=i.subcontract_boq_section_id JOIN subcontract_boqs b ON b.id=s.subcontract_boq_id WHERE b.subcontract_id=?',[$subcontract])->fetch_assoc()['id'];
            }else{
                // Existing legacy lump-sum contracts bill their approved header execution.
                $db->execute("UPDATE subcontracts SET pricing_method='lump_sum',contract_value='5000.00',status='active' WHERE id=?",[$subcontract]);
            }
            $certificate=$certificates->save(new SubcontractCertificateData($subcontract,null,null,null,'2026-01-05',$method==='boq'?null:'50.0000',$item===null?[]:[$item=>'0.5000'],null));
            $certificates->approve($certificate);
            $key=$item??'subcontract:'.$subcontract;
            $rows=array_column($statements->scopeRows($contract,'2026-01-10'),null,'item_id');
            $assert($rows[$key]['current_subcontract_cost']==='2500.00','50 percent of a 5000 subcontract did not yield 2500.');
            $earlier=array_column($statements->scopeRows($contract,'2026-01-04'),null,'item_id');
            $assert($earlier[$key]['current_subcontract_cost']==='0.00','Future execution leaked into an earlier statement date.');

            $manual=$costs->save(new ProjectCostData($project,'2026-01-06',(int)$code['id'],null,'',null,'1000.00',null,null,(int)$code['work_section_id'],'work'));
            $costs->approve($manual);
            if($item!==null){
                $duplicate=$costs->save(new ProjectCostData($project,'2026-01-06',(int)$code['id'],$vendor,'Already included subcontract execution',null,'2500.00',null,$item,(int)$code['work_section_id']));
                $costs->approve($duplicate);
                $eligible=array_map('intval',array_column($statements->getEligibleActualCostsForContract($contract,'2026-01-10'),'id'));
                $assert(!in_array($duplicate,$eligible,true),'Linked subcontract cost offered for duplicate billing.');
                try{$statements->save($contract,new ClientProgressStatementData('2026-01-10',null,null,null,null,[$duplicate],[]));throw new RuntimeException('Duplicate linked cost accepted.');}catch(BusinessRuleException){}
            }
            $first=$statements->save($contract,new ClientProgressStatementData('2026-01-10',null,null,null,null,[$manual],[]));
            $statements->approve($first);
            $saved=$statements->find($first);
            $assert($saved['current_cost_amount']==='3500.00'&&$saved['current_markup_amount']==='525.00'&&$saved['current_statement_amount']==='4025.00','2500 execution plus 1000 manual plus 15 percent did not equal 4025.');
            $snapshot=$saved['cost_scope_snapshot'];
            $stale=$statements->save($contract,new ClientProgressStatementData('2026-01-10',null,null,null,null,[],[]));
            try{$statements->approve($stale);throw new RuntimeException('Execution billed a second time.');}catch(BusinessRuleException){}
            $secondCertificate=$certificates->save(new SubcontractCertificateData($subcontract,null,null,null,'2026-01-15',$method==='boq'?null:'50.0000',$item===null?[]:[$item=>'0.5000'],null));
            $certificates->approve($secondCertificate);
            if($item!==null)$db->execute("UPDATE subcontract_boq_items SET unit_rate='9000.00' WHERE id=?",[$item]);
            $second=$statements->save($contract,new ClientProgressStatementData('2026-01-20',null,null,null,null,[],[]));
            $statements->approve($second);
            $saved=$statements->find($second);
            $assert($saved['current_cost_amount']==='2500.00'&&$saved['current_statement_amount']==='2875.00'&&$saved['cumulative_statement_amount']==='6900.00','New execution was not billed exactly once using the approved source prices.');
            $assert($statements->find($first)['cost_scope_snapshot']===$snapshot,'Earlier approved statement snapshot changed.');
        }
    } finally {
        foreach($contractIds as $id){$db->execute('DELETE d FROM client_progress_statement_costs d JOIN client_progress_statements s ON s.id=d.statement_id WHERE s.client_contract_id=?',[$id]);$db->execute('DELETE FROM client_progress_statements WHERE client_contract_id=?',[$id]);$db->execute('DELETE FROM client_contracts WHERE id=?',[$id]);}
        foreach($projectIds as $id)$db->execute('DELETE FROM project_actual_costs WHERE project_id=?',[$id]);
        foreach($subcontractIds as $id){
            $db->execute('DELETE i FROM subcontract_progress_items i JOIN subcontract_progress_certificates c ON c.id=i.certificate_id WHERE c.subcontract_id=?',[$id]);$db->execute('DELETE FROM subcontract_progress_certificates WHERE subcontract_id=?',[$id]);
            $db->execute('DELETE i FROM subcontract_boq_items i JOIN subcontract_boq_sections s ON s.id=i.subcontract_boq_section_id JOIN subcontract_boqs b ON b.id=s.subcontract_boq_id WHERE b.subcontract_id=?',[$id]);
            $db->execute('DELETE s FROM subcontract_boq_sections s JOIN subcontract_boqs b ON b.id=s.subcontract_boq_id WHERE b.subcontract_id=?',[$id]);$db->execute('DELETE FROM subcontract_boqs WHERE subcontract_id=?',[$id]);$db->execute('DELETE FROM subcontracts WHERE id=?',[$id]);
        }
        if($vendor!==null){$db->execute('DELETE FROM vendor_work_sections WHERE vendor_id=?',[$vendor]);$db->execute('DELETE FROM vendors WHERE id=?',[$vendor]);}
        foreach($projectIds as $id)$db->execute('DELETE FROM projects WHERE id=?',[$id]);
        if($client!==null)$db->execute('DELETE FROM clients WHERE id=?',[$client]);
        if($previousUser!==null)$auth->refresh($previousUser);else$auth->logout();
    }
})();
