<?php
declare(strict_types=1);
use App\Core\Auth\Auth;
use App\Core\Database\Database;
use App\Core\Exceptions\BusinessRuleException;
use App\Modules\PurchaseOrders\DTOs\PurchaseOrderData;
use App\Modules\PurchaseOrders\Services\PurchaseOrderService;
/** @var Database $db */$db=$app->make(Database::class);
/** @var PurchaseOrderService $service */$service=$app->make(PurchaseOrderService::class);
/** @var Auth $auth */$auth=$app->make(Auth::class);
$user=$db->execute('SELECT id,name,username FROM users WHERE is_active=1 LIMIT 1')->fetch_assoc();$costCode=$db->execute('SELECT id FROM cost_codes WHERE is_active=1 LIMIT 1')->fetch_assoc();if(!$user||!$costCode)throw new RuntimeException('Purchase order fixtures unavailable.');
$suffix=(string)random_int(100000,999999);$clientId=$vendorId=$projectId=0;$orderIds=[];
try{
    $db->execute("INSERT INTO clients(client_code,client_type,name) VALUES(?,'individual','PO Client')",['POC-'.$suffix]);$clientId=(int)$db->connection()->insert_id;
    $db->execute("INSERT INTO vendors(vendor_code,vendor_type,name) VALUES(?,'supplier','PO Supplier')",['POV-'.$suffix]);$vendorId=(int)$db->connection()->insert_id;
    $db->execute("INSERT INTO projects(project_code,name,client_id,project_type,status) VALUES(?,'PO Project',?,'contracting','active')",['POP-'.$suffix,$clientId]);$projectId=(int)$db->connection()->insert_id;$auth->login($user);
    $financialBefore=[
        'costs'=>(int)$db->execute('SELECT COUNT(*) n FROM project_actual_costs')->fetch_assoc()['n'],
        'invoices'=>(int)$db->execute('SELECT COUNT(*) n FROM supplier_invoices')->fetch_assoc()['n'],
        'payments'=>(int)$db->execute('SELECT COUNT(*) n FROM supplier_payments')->fetch_assoc()['n'],
    ];
    $data=new PurchaseOrderData($vendorId,$projectId,'2026-09-18','2026-09-30',null,'Commitment only',[
        ['description'=>'A','cost_code_id'=>(int)$costCode['id'],'quantity'=>'0.1000','unit_price'=>'0.20','sort_order'=>1],
        ['description'=>'B','cost_code_id'=>(int)$costCode['id'],'quantity'=>'0.2000','unit_price'=>'0.30','sort_order'=>2],
    ]);
    $orderId=$service->save($data);$orderIds[]=$orderId;$draft=$service->details($orderId);if($draft['displayTotal']!=='0.08'||$draft['order']['total_amount']!==null)throw new RuntimeException('Exact draft PO total failed.');
    $service->approve($orderId);$approved=$service->details($orderId);if($approved['order']['status']!=='approved'||$approved['order']['total_amount']!=='0.08'||$approved['lines'][0]['cost_code_snapshot']===null)throw new RuntimeException('PO approval or snapshots failed.');
    try{$service->approve($orderId);throw new RuntimeException('Competing PO approval was accepted.');}catch(BusinessRuleException){}
    try{$service->save($data,$orderId);throw new RuntimeException('Approved PO was mutable.');}catch(BusinessRuleException){}
    try{$service->cancel($orderId);throw new RuntimeException('Approved PO was cancellable.');}catch(BusinessRuleException){}
    $cancelledId=$service->save(new PurchaseOrderData($vendorId,$projectId,'2026-09-18',null,null,null,[['description'=>'Cancelled','cost_code_id'=>(int)$costCode['id'],'quantity'=>'1.0000','unit_price'=>'5.00','sort_order'=>1]]));$orderIds[]=$cancelledId;$service->cancel($cancelledId);
    try{$service->save($data,$cancelledId);throw new RuntimeException('Cancelled PO was mutable.');}catch(BusinessRuleException){}
    try{$service->cancel($cancelledId);throw new RuntimeException('Competing PO cancellation was accepted.');}catch(BusinessRuleException){}
    $overflowData=new PurchaseOrderData($vendorId,$projectId,'2026-09-18',null,null,null,[
        ['description'=>'Large A','cost_code_id'=>(int)$costCode['id'],'quantity'=>'99999999.9999','unit_price'=>'99999999.99','sort_order'=>1],
        ['description'=>'Large B','cost_code_id'=>(int)$costCode['id'],'quantity'=>'99999999.9999','unit_price'=>'99999999.99','sort_order'=>2],
    ]);
    try{$service->save($overflowData);throw new RuntimeException('Overflowing PO total was saved.');}catch(BusinessRuleException $exception){if(!str_contains($exception->getMessage(),'DECIMAL(18,2)'))throw $exception;}
    $db->execute("INSERT INTO purchase_orders(po_code,vendor_id,project_id,po_date,status,created_by) VALUES(?,?,?,'2026-09-18','draft',?)",['PO-OVERFLOW-'.$suffix,$vendorId,$projectId,$user['id']]);$overflowId=(int)$db->connection()->insert_id;$orderIds[]=$overflowId;
    foreach($overflowData->lines as $line)$db->execute('INSERT INTO purchase_order_lines(purchase_order_id,description,cost_code_id,quantity,unit_price,sort_order) VALUES(?,?,?,?,?,?)',[$overflowId,$line['description'],$line['cost_code_id'],$line['quantity'],$line['unit_price'],$line['sort_order']]);
    try{$service->approve($overflowId);throw new RuntimeException('Overflowing PO total was approved.');}catch(BusinessRuleException $exception){if(!str_contains($exception->getMessage(),'DECIMAL(18,2)'))throw $exception;}
    if($service->find($overflowId)['status']!=='draft')throw new RuntimeException('Overflowing PO approval changed lifecycle state.');
    $financialAfter=['costs'=>(int)$db->execute('SELECT COUNT(*) n FROM project_actual_costs')->fetch_assoc()['n'],'invoices'=>(int)$db->execute('SELECT COUNT(*) n FROM supplier_invoices')->fetch_assoc()['n'],'payments'=>(int)$db->execute('SELECT COUNT(*) n FROM supplier_payments')->fetch_assoc()['n']];
    if($financialAfter!==$financialBefore)throw new RuntimeException('Purchase order lifecycle created a financial effect.');
}finally{$auth->logout();foreach($orderIds as $id){$db->execute('DELETE FROM purchase_order_lines WHERE purchase_order_id=?',[$id]);$db->execute('DELETE FROM purchase_orders WHERE id=?',[$id]);}if($projectId)$db->execute('DELETE FROM projects WHERE id=?',[$projectId]);if($vendorId)$db->execute('DELETE FROM vendors WHERE id=?',[$vendorId]);if($clientId)$db->execute('DELETE FROM clients WHERE id=?',[$clientId]);}
