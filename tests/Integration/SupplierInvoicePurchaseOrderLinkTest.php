<?php
declare(strict_types=1);
use App\Core\Auth\Auth;
use App\Core\Database\Database;
use App\Core\Exceptions\BusinessRuleException;
use App\Modules\PurchaseOrders\DTOs\PurchaseOrderData;
use App\Modules\PurchaseOrders\Services\PurchaseOrderService;
use App\Modules\SupplierInvoices\DTOs\SupplierInvoiceData;
use App\Modules\SupplierInvoices\Services\SupplierInvoiceService;
/** @var Database $db */$db=$app->make(Database::class);
/** @var PurchaseOrderService $purchaseOrders */$purchaseOrders=$app->make(PurchaseOrderService::class);
/** @var SupplierInvoiceService $invoices */$invoices=$app->make(SupplierInvoiceService::class);
/** @var Auth $auth */$auth=$app->make(Auth::class);
$user=$db->execute('SELECT id,name,username FROM users WHERE is_active=1 LIMIT 1')->fetch_assoc();$costCode=$db->execute('SELECT id FROM cost_codes WHERE is_active=1 LIMIT 1')->fetch_assoc();if(!$user||!$costCode)throw new RuntimeException('Supplier invoice PO link fixtures unavailable.');
$suffix=(string)random_int(100000,999999);$clientId=$vendorId=$otherVendorId=$projectId=$otherProjectId=0;$purchaseOrderIds=[];$invoiceIds=[];
try{
    $db->execute("INSERT INTO clients(client_code,client_type,name) VALUES(?,'individual','PO Link Client')",['POLC-'.$suffix]);$clientId=(int)$db->connection()->insert_id;
    $db->execute("INSERT INTO vendors(vendor_code,vendor_type,name) VALUES(?,'supplier','PO Link Supplier')",['POLV-'.$suffix]);$vendorId=(int)$db->connection()->insert_id;
    $db->execute("INSERT INTO vendors(vendor_code,vendor_type,name) VALUES(?,'supplier','Other PO Supplier')",['POLVO-'.$suffix]);$otherVendorId=(int)$db->connection()->insert_id;
    $db->execute("INSERT INTO projects(project_code,name,client_id,project_type,status) VALUES(?,'PO Link Project',?,'contracting','active')",['POLP-'.$suffix,$clientId]);$projectId=(int)$db->connection()->insert_id;
    $db->execute("INSERT INTO projects(project_code,name,client_id,project_type,status) VALUES(?,'Other PO Project',?,'contracting','active')",['POLPO-'.$suffix,$clientId]);$otherProjectId=(int)$db->connection()->insert_id;$auth->login($user);
    $poData=new PurchaseOrderData($vendorId,$projectId,'2026-09-18',null,null,null,[['description'=>'Committed material','cost_code_id'=>(int)$costCode['id'],'quantity'=>'2.0000','unit_price'=>'12.50','sort_order'=>1]]);
    $purchaseOrderId=$purchaseOrders->save($poData);$purchaseOrderIds[]=$purchaseOrderId;
    $invoiceData=static fn(int $vendor,int $project,?int $po,string $number):SupplierInvoiceData=>new SupplierInvoiceData($vendor,$project,$po,$number,'2026-09-18',null,null,null,[['description'=>'Recognized material','cost_code_id'=>(int)$costCode['id'],'amount'=>'25.00','sort_order'=>1]]);
    foreach([$purchaseOrderId,999999999] as $invalidPo){try{$invoices->save($invoiceData($vendorId,$projectId,$invalidPo,'BAD-'.$invalidPo));throw new RuntimeException('Invalid or draft PO was linked.');}catch(BusinessRuleException){}}
    $costsBefore=(int)$db->execute('SELECT COUNT(*) n FROM project_actual_costs')->fetch_assoc()['n'];$purchaseOrders->approve($purchaseOrderId);if((int)$db->execute('SELECT COUNT(*) n FROM project_actual_costs')->fetch_assoc()['n']!==$costsBefore)throw new RuntimeException('PO approval created a recognized cost.');
    try{$invoices->save($invoiceData($otherVendorId,$projectId,$purchaseOrderId,'MISMATCH-V'));throw new RuntimeException('PO vendor mismatch was accepted.');}catch(BusinessRuleException){}
    try{$invoices->save($invoiceData($vendorId,$otherProjectId,$purchaseOrderId,'MISMATCH-P'));throw new RuntimeException('PO project mismatch was accepted.');}catch(BusinessRuleException){}
    $invoiceId=$invoices->save($invoiceData($vendorId,$projectId,$purchaseOrderId,'LINKED-01'));$invoiceIds[]=$invoiceId;$purchaseOrderCode=(string)$purchaseOrders->find($purchaseOrderId)['po_code'];$invoices->approve($invoiceId);
    $approved=$invoices->find($invoiceId);if($approved['status']!=='approved'||(int)$approved['purchase_order_id']!==$purchaseOrderId||$approved['purchase_order_code_snapshot']!==$purchaseOrderCode)throw new RuntimeException('Approved linked invoice did not freeze the PO snapshot.');
    $costCount=(int)$db->execute("SELECT COUNT(*) n FROM project_actual_costs WHERE source_type='supplier_invoice' AND source_id=?",[$invoiceId])->fetch_assoc()['n'];if($costCount!==1)throw new RuntimeException('Linked invoice did not recognize exactly one cost per line.');
    try{$invoices->approve($invoiceId);throw new RuntimeException('Linked invoice double approval was accepted.');}catch(BusinessRuleException){}
    $costCountAfter=(int)$db->execute("SELECT COUNT(*) n FROM project_actual_costs WHERE source_type='supplier_invoice' AND source_id=?",[$invoiceId])->fetch_assoc()['n'];if($costCountAfter!==$costCount)throw new RuntimeException('Duplicate Project Actual Cost was created.');
    $db->execute('UPDATE purchase_orders SET po_code=? WHERE id=?',['PO-RENAMED-'.$suffix,$purchaseOrderId]);if($invoices->find($invoiceId)['purchase_order_code_snapshot']!==$purchaseOrderCode)throw new RuntimeException('Supplier invoice PO snapshot was not frozen.');
    if($purchaseOrders->find($purchaseOrderId)['status']!=='approved')throw new RuntimeException('Linked invoice changed PO commitment lifecycle.');
}finally{
    $auth->logout();foreach($invoiceIds as $id){$db->execute("DELETE FROM project_actual_costs WHERE source_type='supplier_invoice' AND source_id=?",[$id]);$db->execute('DELETE FROM supplier_invoice_lines WHERE supplier_invoice_id=?',[$id]);$db->execute('DELETE FROM supplier_invoices WHERE id=?',[$id]);}foreach($purchaseOrderIds as $id){$db->execute('DELETE FROM purchase_order_lines WHERE purchase_order_id=?',[$id]);$db->execute('DELETE FROM purchase_orders WHERE id=?',[$id]);}if($otherProjectId)$db->execute('DELETE FROM projects WHERE id=?',[$otherProjectId]);if($projectId)$db->execute('DELETE FROM projects WHERE id=?',[$projectId]);if($otherVendorId)$db->execute('DELETE FROM vendors WHERE id=?',[$otherVendorId]);if($vendorId)$db->execute('DELETE FROM vendors WHERE id=?',[$vendorId]);if($clientId)$db->execute('DELETE FROM clients WHERE id=?',[$clientId]);
}
