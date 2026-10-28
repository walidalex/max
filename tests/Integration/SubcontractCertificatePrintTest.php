<?php

declare(strict_types=1);

use App\Core\Auth\Auth;
use App\Core\Database\Database;
use App\Core\View\View;
use App\Modules\SubcontractCertificates\DTOs\SubcontractCertificateData;
use App\Modules\SubcontractCertificates\Services\SubcontractCertificateService;

$db=$app->make(Database::class);
$service=$app->make(SubcontractCertificateService::class);
$auth=$app->make(Auth::class);
$originalUser=$auth->user();
$suffix=bin2hex(random_bytes(5));
$client=$project=$vendor=$subcontract=null;
try{
    $user=$db->execute('SELECT id,name,username FROM users WHERE is_active=1 LIMIT 1')->fetch_assoc();
    if(!$user)throw new RuntimeException('Print fixture user missing.');
    $auth->login($user);
    $db->execute("INSERT INTO clients(client_code,client_type,name) VALUES(?,'individual',?)",['PCL-'.$suffix,'<script>Client</script>']);
    $client=(int)$db->connection()->insert_id;
    $db->execute("INSERT INTO projects(project_code,name,client_id,project_type,status) VALUES(?,'Print Project',?,'contracting','active')",['PPR-'.$suffix,$client]);
    $project=(int)$db->connection()->insert_id;
    $db->execute("INSERT INTO vendors(vendor_code,vendor_type,name) VALUES(?,'subcontractor','Print Vendor')",['PVN-'.$suffix]);
    $vendor=(int)$db->connection()->insert_id;
    $db->execute("INSERT INTO subcontracts(subcontract_code,project_id,vendor_id,pricing_method,title,contract_date,contract_value,status) VALUES(?,?,?,'lump_sum','Print Contract','2026-01-01','1000.00','active')",['PSC-'.$suffix,$project,$vendor]);
    $subcontract=(int)$db->connection()->insert_id;
    $insertPayment=static function(string $code,string $status)use($db,$subcontract,$vendor,$project,$user):void{
        $db->execute("INSERT INTO subcontract_payments(payment_code,subcontract_id,vendor_id,project_id,payment_date,amount,payment_method,status,created_by,posted_by,posted_at,approved_progress_percentage_snapshot,approved_earned_value_snapshot,previous_payments_snapshot,available_payment_snapshot) VALUES(?,?,?,?,'2026-01-01','100.00','cash',?,?,?,NOW(),'50','500.00','0.00','500.00')",[$code,$subcontract,$vendor,$project,$status,(int)$user['id'],(int)$user['id']]);
    };
    $insertPayment('PP1-'.$suffix,'posted');
    $insertPayment('PPD-'.$suffix,'draft');
    $certificate=$service->save(new SubcontractCertificateData($subcontract,null,null,null,'2026-01-01','50.0000',[],null));
    $preview=$service->printData($certificate);
    if($preview['frozen']||$preview['totals']['current_value']!=='500.00'||count($preview['payments'])!==1)throw new RuntimeException('Draft print preview incorrect.');
    $service->approve($certificate);
    $before=$service->printData($certificate);
    if(!$before['frozen']||$before['totals']['paid_total']!=='100.00'||$before['totals']['balance']!=='400.00')throw new RuntimeException('Approval print snapshot incorrect.');
    $insertPayment('PP2-'.$suffix,'posted');
    $db->execute('UPDATE vendors SET name=? WHERE id=?',['Changed Vendor',$vendor]);
    $after=$service->printData($certificate);
    if($before['totals']!==$after['totals']||$before['payments']!==$after['payments']||$before['identity']!==$after['identity'])throw new RuntimeException('Historical print changed after later payment or master edit.');
    $html=$app->make(View::class)->render('modules/subcontract-certificates/print',['title'=>'Print',...$after,'company'=>['name'=>'Company'],'companyLogo'=>null],'layouts/certificate-print');
    if(str_contains($html,'<script>Client</script>')||!str_contains($html,'&lt;script&gt;Client&lt;/script&gt;'))throw new RuntimeException('Printed identity not escaped.');
    $db->execute('UPDATE subcontract_progress_certificates SET print_snapshot=NULL WHERE id=?',[$certificate]);
    $legacy=$service->printData($certificate);
    if($legacy['frozen']||count($legacy['payments'])!==2)throw new RuntimeException('Legacy payment fallback incorrect.');
}finally{
    if($subcontract!==null){
        $db->execute('DELETE FROM subcontract_payments WHERE subcontract_id=?',[$subcontract]);
        $db->execute('DELETE FROM subcontract_progress_certificates WHERE subcontract_id=?',[$subcontract]);
        $db->execute('DELETE FROM subcontracts WHERE id=?',[$subcontract]);
    }
    if($vendor!==null)$db->execute('DELETE FROM vendors WHERE id=?',[$vendor]);
    if($project!==null)$db->execute('DELETE FROM projects WHERE id=?',[$project]);
    if($client!==null)$db->execute('DELETE FROM clients WHERE id=?',[$client]);
    if($originalUser!==null)$auth->refresh($originalUser);else $auth->logout();
}
