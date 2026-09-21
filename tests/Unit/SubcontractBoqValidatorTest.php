<?php
declare(strict_types=1);
use App\Core\Exceptions\ValidationException;
use App\Core\Validation\Validator;
use App\Modules\SubcontractBoq\Validators\BoqItemValidator;
$validator=new BoqItemValidator(new Validator());
$quantity=$validator->validate(['section_id'=>'1','cost_code_id'=>'','description'=>'بند مخصص كمي','pricing_type'=>'quantity','unit_id'=>'2','quantity'=>'3.5000','unit_rate'=>'125.75','sort_order'=>'1']);
if($quantity->costCodeId!==null||$quantity->quantity!=='3.5000'||$quantity->unitRate!=='125.75')throw new RuntimeException('Custom quantity item validation failed.');
$lump=$validator->validate(['section_id'=>'1','cost_code_id'=>'','description'=>'بند مخصص مقطوعية','pricing_type'=>'lump_sum','lump_sum_amount'=>'9999999999999999.12','sort_order'=>'1']);
if($lump->costCodeId!==null||$lump->unitId!==null||$lump->lumpSumAmount!=='9999999999999999.12')throw new RuntimeException('Custom lump-sum item validation failed.');
try{$validator->validate(['section_id'=>'1','description'=>'بند غير مكتمل','pricing_type'=>'quantity','sort_order'=>'1']);throw new RuntimeException('Incomplete quantity item accepted.');}catch(ValidationException){}
$source=file_get_contents(dirname(__DIR__,2).'/src/Modules/SubcontractBoq/Validators/BoqItemValidator.php');
if(str_contains((string)$source,'(float)')||str_contains((string)$source,'number_format('))throw new RuntimeException('Subcontract BOQ validator uses float conversion.');
