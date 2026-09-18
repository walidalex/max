<?php
declare(strict_types=1);
use App\Core\Database\Database;
use App\Core\Exceptions\BusinessRuleException;
use App\Modules\Accounting\DTOs\AccountData;
use App\Modules\Accounting\Services\AccountingService;
/** @var Database $db */$db=$app->make(Database::class);/** @var AccountingService $service */$service=$app->make(AccountingService::class);
$postable=(int)$db->execute('SELECT id FROM accounts WHERE is_postable=1 LIMIT 1')->fetch_assoc()['id'];
try{$service->saveAccount(new AccountData('999998','حساب اختبار','Invalid Child',$postable,'asset','debit',true,false,true));throw new RuntimeException('Postable account was accepted as a parent.');}catch(BusinessRuleException){}
$created=$db->execute('SELECT COUNT(*) n FROM accounts WHERE account_code=?',['999998'])->fetch_assoc();if((int)$created['n']!==0)throw new RuntimeException('Rejected hierarchy account was persisted.');
