<?php
declare(strict_types=1);

use App\Core\Auth\Auth;
use App\Core\Database\Database;
use App\Core\Exceptions\BusinessRuleException;
use App\Modules\Accounting\Services\AccountingService;
use App\Modules\EmployeeCustodies\DTOs\CustodyData;
use App\Modules\EmployeeCustodies\DTOs\SettlementData;
use App\Modules\EmployeeCustodies\Services\CustodyService;

/** @var Database $db */$db=$app->make(Database::class);/** @var CustodyService $service */$service=$app->make(CustodyService::class);/** @var AccountingService $accounting */$accounting=$app->make(AccountingService::class);/** @var Auth $auth */$auth=$app->make(Auth::class);
$auth->login($db->execute('SELECT id,name,username FROM users WHERE is_active=1 LIMIT 1')->fetch_assoc());
$year=2093;$suffix=(string)random_int(100000,999999);$client=$employee=0;$projects=[];$custodies=[];$costIds=[];$oldMappings=[];
try{
 $accounting->createYear($year);
 $db->execute('INSERT INTO clients(client_code,client_type,name) VALUES(?,\'individual\',?)',['HARD-CL-'.$suffix,'Hardening Client']);$client=(int)$db->connection()->insert_id;
 foreach(['A','B']as$n){$db->execute('INSERT INTO projects(project_code,name,client_id,project_type,status) VALUES(?,?,?,\'contracting\',\'active\')',['HARD-PRJ-'.$n.'-'.$suffix,'Hardening Project '.$n,$client]);$projects[]=(int)$db->connection()->insert_id;}
 $db->execute('INSERT INTO employees(employee_code,name) VALUES(?,?)',['HARD-EMP-'.$suffix,'Hardening Employee']);$employee=(int)$db->connection()->insert_id;
 $cash=(int)$db->execute('SELECT account_id FROM accounting_setup WHERE mapping_key=?',['cash_on_hand'])->fetch_assoc()['account_id'];
 $costIds=array_map('intval',array_column($db->execute('SELECT id FROM cost_codes WHERE is_active=1 ORDER BY id LIMIT 2')->fetch_all(MYSQLI_ASSOC),'id'));
 if(count($costIds)<2)throw new RuntimeException('Two cost codes are required for hardening tests.');
 foreach($costIds as$cost){$row=$db->execute('SELECT account_id FROM accounting_cost_code_mappings WHERE cost_code_id=?',[$cost])->fetch_assoc();$oldMappings[$cost]=$row===null?null:(int)$row['account_id'];}
 $expense=(int)$db->execute('SELECT id FROM accounts WHERE account_code=?',['590001'])->fetch_assoc()['id'];$revenue=(int)$db->execute('SELECT id FROM accounts WHERE account_code=?',['410001'])->fetch_assoc()['id'];
 try{$accounting->saveCostCodeMapping($costIds[0],$revenue);throw new RuntimeException('Non-expense cost-code mapping was accepted.');}catch(BusinessRuleException){}
 $accounting->saveCostCodeMapping($costIds[0],$expense);$db->execute('DELETE FROM accounting_cost_code_mappings WHERE cost_code_id=?',[$costIds[1]]);
 $configured=array_column($service->references()['funding_accounts'],'id');if(!in_array($cash,array_map('intval',$configured),true))throw new RuntimeException('Configured custody funding account was not exposed.');

 $fixed=$service->save(new CustodyData($employee,$projects[0],$year.'-01-05','10.00','cash',$cash,'Fixed project'));$custodies[]=$fixed;$service->issue($fixed);
 try{$service->settle($fixed,new SettlementData('expense',$year.'-01-06','Wrong project','1.00',$projects[1],$costIds[0],null,null));throw new RuntimeException('Mismatched custody project was accepted.');}catch(BusinessRuleException){}
 try{$service->settle($fixed,new SettlementData('expense',$year.'-01-04','Early settlement','1.00',$projects[0],$costIds[0],null,null));throw new RuntimeException('Settlement before issue date was accepted.');}catch(BusinessRuleException){}
 $count=$db->execute('SELECT COUNT(*) n FROM employee_custody_settlements WHERE custody_id=?',[$fixed])->fetch_assoc();if((int)$count['n']!==0)throw new RuntimeException('Rejected settlement created a record.');

 $flexible=$service->save(new CustodyData($employee,null,$year.'-01-07','2.00','cash',$cash,'Flexible project'));$custodies[]=$flexible;$service->issue($flexible);$service->settle($flexible,new SettlementData('expense',$year.'-01-08','Mapped expense','2.00',$projects[1],$costIds[0],null,null));
 $effect=$db->execute('SELECT s.journal_entry_id,s.project_actual_cost_id,p.project_id pac_project FROM employee_custody_settlements s JOIN project_actual_costs p ON p.id=s.project_actual_cost_id WHERE s.custody_id=?',[$flexible])->fetch_assoc();if((int)$effect['pac_project']!==$projects[1])throw new RuntimeException('PAC used the wrong settlement project.');
 $dimensions=$db->execute('SELECT COUNT(*) n FROM journal_lines WHERE journal_entry_id=? AND project_id<>?',[(int)$effect['journal_entry_id'],$projects[1]])->fetch_assoc();if((int)$dimensions['n']!==0)throw new RuntimeException('PAC and GL project dimensions disagree.');

 $unmapped=$service->save(new CustodyData($employee,null,$year.'-01-09','1.00','cash',$cash,'Missing mapping'));$custodies[]=$unmapped;$service->issue($unmapped);
 try{$service->settle($unmapped,new SettlementData('expense',$year.'-01-10','Unmapped expense','1.00',$projects[0],$costIds[1],null,null));throw new RuntimeException('Missing cost-code mapping was accepted.');}catch(BusinessRuleException){}
 $effects=$db->execute('SELECT (SELECT COUNT(*) FROM employee_custody_settlements WHERE custody_id=?) settlements,(SELECT COUNT(*) FROM project_actual_costs WHERE source_type=\'employee_custody_settlement\' AND description=?) costs',[$unmapped,'Unmapped expense'])->fetch_assoc();if((int)$effects['settlements']!==0||(int)$effects['costs']!==0)throw new RuntimeException('Missing mapping created financial effects.');
}finally{
 $auth->logout();if($custodies){$marks=implode(',',array_fill(0,count($custodies),'?'));$journalRows=$db->execute('SELECT journal_entry_id id FROM employee_custody_settlements WHERE custody_id IN('.$marks.') UNION SELECT issue_journal_entry_id id FROM employee_custodies WHERE id IN('.$marks.')',[...$custodies,...$custodies])->fetch_all(MYSQLI_ASSOC);$costRows=$db->execute('SELECT project_actual_cost_id id FROM employee_custody_settlements WHERE custody_id IN('.$marks.')',$custodies)->fetch_all(MYSQLI_ASSOC);$db->execute('DELETE FROM employee_custody_settlements WHERE custody_id IN('.$marks.')',$custodies);foreach($custodies as$id)$db->execute('DELETE FROM employee_custodies WHERE id=?',[$id]);foreach($costRows as$row)if($row['id']!==null)$db->execute('DELETE FROM project_actual_costs WHERE id=?',[(int)$row['id']]);foreach($journalRows as$row)if($row['id']!==null){$db->execute('DELETE FROM journal_lines WHERE journal_entry_id=?',[(int)$row['id']]);$db->execute('DELETE FROM journal_entries WHERE id=?',[(int)$row['id']]);}}
 foreach($oldMappings as$cost=>$account){if($account===null)$db->execute('DELETE FROM accounting_cost_code_mappings WHERE cost_code_id=?',[$cost]);else$db->execute('INSERT INTO accounting_cost_code_mappings(cost_code_id,account_id) VALUES(?,?) ON DUPLICATE KEY UPDATE account_id=VALUES(account_id)',[$cost,$account]);}
 if($employee)$db->execute('DELETE FROM employees WHERE id=?',[$employee]);foreach($projects as$id)$db->execute('DELETE FROM projects WHERE id=?',[$id]);if($client)$db->execute('DELETE FROM clients WHERE id=?',[$client]);$db->execute('DELETE FROM accounting_periods WHERE fiscal_year=?',[$year]);
}
