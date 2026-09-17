<?php
declare(strict_types=1);
use App\Core\Exceptions\ValidationException;
use App\Core\Validation\Validator;
use App\Modules\PurchaseOrders\Validators\PurchaseOrderValidator;
$validator=new PurchaseOrderValidator(new Validator());
$data=$validator->validate(['vendor_id'=>'1','project_id'=>'2','po_date'=>'2026-09-18','expected_date'=>'2026-09-20','reference'=>'','notes'=>'','lines'=>[['description'=>'مواد','cost_code_id'=>'3','quantity'=>'0002.5','unit_price'=>'10.1']]]);
if($data->lines[0]['quantity']!=='2.5000'||$data->lines[0]['unit_price']!=='10.10'||$data->reference!==null)throw new RuntimeException('Purchase order decimal normalization failed.');
foreach([
    ['vendor_id'=>'1','project_id'=>'2','po_date'=>'bad','lines'=>[]],
    ['vendor_id'=>'1','project_id'=>'2','po_date'=>'2026-09-20','expected_date'=>'2026-09-19','lines'=>[['description'=>'X','cost_code_id'=>'1','quantity'=>'1','unit_price'=>'1']]],
    ['vendor_id'=>'1','project_id'=>'2','po_date'=>'2026-09-18','lines'=>[['description'=>'X','cost_code_id'=>'1','quantity'=>'0','unit_price'=>'1']]],
    ['vendor_id'=>'1','project_id'=>'2','po_date'=>'2026-09-18','lines'=>[['description'=>'X','cost_code_id'=>'1','quantity'=>'1','unit_price'=>'0.001']]],
] as $invalid){try{$validator->validate($invalid);throw new RuntimeException('Invalid purchase order was accepted.');}catch(ValidationException){}}
