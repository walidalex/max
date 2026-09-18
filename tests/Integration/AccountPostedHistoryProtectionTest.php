<?php
declare(strict_types=1);

use App\Core\Auth\Auth;
use App\Core\Database\Database;
use App\Core\Exceptions\BusinessRuleException;
use App\Modules\Accounting\DTOs\AccountData;
use App\Modules\Accounting\Services\AccountingService;

/** @var Database $db */$db=$app->make(Database::class);
/** @var AccountingService $service */$service=$app->make(AccountingService::class);
/** @var Auth $auth */$auth=$app->make(Auth::class);
$user=$db->execute('SELECT id,name,username FROM users WHERE is_active=1 LIMIT 1')->fetch_assoc();$auth->login($user);
$year=2094;$journal=0;
$cash=$db->execute('SELECT * FROM accounts WHERE account_code=?',['111001'])->fetch_assoc();
$capital=$db->execute('SELECT * FROM accounts WHERE account_code=?',['310001'])->fetch_assoc();
$line=static fn(int$a,string$d,string$c,int$o):array=>['account_id'=>$a,'description'=>'History guard','debit'=>$d,'credit'=>$c,'project_id'=>null,'client_id'=>null,'vendor_id'=>null,'subcontract_id'=>null,'employee_id'=>null,'cost_code_id'=>null,'sort_order'=>$o];
try{
 $service->createYear($year);
 $journal=$service->createPostedAutomatic($year.'-01-10','History guard','history_guard',random_int(1000000,9999999),null,[$line((int)$cash['id'],'5.00','0.00',1),$line((int)$capital['id'],'0.00','5.00',2)]);
 $before=$service->trialBalance($year.'-01-01',$year.'-12-31');
 try{$service->saveAccount(new AccountData((string)$cash['account_code'],(string)$cash['name_ar'],(string)$cash['name_en'],$cash['parent_id']===null?null:(int)$cash['parent_id'],(string)$cash['account_type'],(string)$cash['normal_balance'],false,(bool)$cash['is_control'],(bool)$cash['is_active']),(int)$cash['id']);throw new RuntimeException('Posted account was made non-postable.');}catch(BusinessRuleException){}
 $after=$service->trialBalance($year.'-01-01',$year.'-12-31');
 if($before!==$after)throw new RuntimeException('Trial Balance changed after rejected account mutation.');
 $stored=$db->execute('SELECT is_postable FROM accounts WHERE id=?',[(int)$cash['id']])->fetch_assoc();if(!(bool)$stored['is_postable'])throw new RuntimeException('Posted account protection did not persist.');
}finally{
 $auth->logout();if($journal){$db->execute('DELETE FROM journal_lines WHERE journal_entry_id=?',[$journal]);$db->execute('DELETE FROM journal_entries WHERE id=?',[$journal]);}$db->execute('DELETE FROM accounting_periods WHERE fiscal_year=?',[$year]);
}
