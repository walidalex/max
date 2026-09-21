<?php
declare(strict_types=1);
use App\Core\Database\Database;
use App\Modules\SubcontractPayments\Repositories\SubcontractPaymentRepository;
/** @var Database $db */
$db=$app->make(Database::class);
/** @var SubcontractPaymentRepository $repo */
$repo=$app->make(SubcontractPaymentRepository::class);
$accounts=$repo->paymentAccounts();
$cash=array_values(array_filter($accounts,static fn(array$a):bool=>$a['channel']==='cash'));
$bank=array_values(array_filter($accounts,static fn(array$a):bool=>$a['channel']==='bank'));
if($cash===[]||$bank===[])throw new RuntimeException('Treasury or bank payment account tree is empty.');
$db->transaction(function()use($repo,$cash,$bank):void{
    if($repo->paymentAccount((int)$cash[0]['id'],'cash',true)===null)throw new RuntimeException('Cash payment account lock failed.');
    if($repo->paymentAccount((int)$bank[0]['id'],'bank',true)===null)throw new RuntimeException('Bank payment account lock failed.');
    if($repo->paymentAccount((int)$cash[0]['id'],'bank',true)!==null)throw new RuntimeException('Cash account accepted as bank account.');
});
