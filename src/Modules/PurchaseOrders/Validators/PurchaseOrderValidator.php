<?php
declare(strict_types=1);
namespace App\Modules\PurchaseOrders\Validators;
use App\Core\Exceptions\ValidationException;
use App\Core\Validation\Validator;
use App\Modules\PurchaseOrders\DTOs\PurchaseOrderData;
final class PurchaseOrderValidator
{
    public function __construct(private readonly Validator $validator) {}
    public function validate(array $input): PurchaseOrderData
    {
        $errors=[];$vendor=$this->id($input['vendor_id']??null);$project=$this->id($input['project_id']??null);$date=$this->date((string)($input['po_date']??''),false);$expected=$this->date((string)($input['expected_date']??''),true);
        if($vendor===null)$errors['vendor_id'][]='المورد غير صالح.';if($project===null)$errors['project_id'][]='المشروع غير صالح.';if($date===null)$errors['po_date'][]='تاريخ أمر الشراء غير صالح.';if(trim((string)($input['expected_date']??''))!==''&&$expected===null)$errors['expected_date'][]='تاريخ التوريد المتوقع غير صالح.';if($date!==null&&$expected!==null&&$expected<$date)$errors['expected_date'][]='تاريخ التوريد المتوقع يجب ألا يسبق تاريخ أمر الشراء.';
        $lines=[];$rawLines=(array)($input['lines']??[]);if(count($rawLines)>200)$errors['lines'][]='الحد الأقصى 200 بند في أمر الشراء.';
        foreach(array_slice($rawLines,0,200) as $line){$description=trim((string)($line['description']??''));$code=$this->id($line['cost_code_id']??null);$quantity=$this->decimal((string)($line['quantity']??''),4,8,false);$price=$this->decimal((string)($line['unit_price']??''),2,8,true);if($description===''||mb_strlen($description)>500||$code===null||$quantity===null||$price===null){$errors['lines'][]='يرجى استكمال وصف وكود وكمية وسعر كل بند بصورة صحيحة.';continue;}$lines[]=['description'=>$description,'cost_code_id'=>$code,'quantity'=>$quantity,'unit_price'=>$price,'sort_order'=>count($lines)+1];}
        if($lines===[])$errors['lines'][]='يجب إضافة بند واحد على الأقل.';if(!$this->validator->validate($input,['reference'=>'string|max:190','notes'=>'string|max:5000']))$errors=array_merge_recursive($errors,$this->validator->errors());if($errors)throw new ValidationException($errors);$nullable=static fn(mixed $value):?string=>trim((string)$value)===''?null:trim((string)$value);
        return new PurchaseOrderData((int)$vendor,(int)$project,(string)$date,$expected,$nullable($input['reference']??''),$nullable($input['notes']??''),$lines);
    }
    private function id(mixed $value):?int{$id=filter_var($value,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);return$id===false?null:$id;}
    private function date(string $value,bool $optional):?string{$value=trim($value);if($optional&&$value==='')return null;$date=\DateTimeImmutable::createFromFormat('!Y-m-d',$value);return$date&&$date->format('Y-m-d')===$value?$value:null;}
    private function decimal(string $value,int $scale,int $integerDigits,bool $allowZero):?string{$value=trim($value);if(!preg_match('/^\d{1,'.$integerDigits.'}(?:\.\d{1,'.$scale.'})?$/',$value))return null;[$whole,$fraction]=array_pad(explode('.',$value,2),2,'');$whole=ltrim($whole,'0');$normalized=($whole===''?'0':$whole).'.'.str_pad($fraction,$scale,'0');return!$allowZero&&$normalized==='0.'.str_repeat('0',$scale)?null:$normalized;}
}
