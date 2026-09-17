<?php
declare(strict_types=1);
namespace App\Modules\SupplierInvoices\Validators;
use App\Core\Exceptions\ValidationException;
use App\Core\Validation\Validator;
use App\Modules\SupplierInvoices\DTOs\SupplierInvoiceData;
final class SupplierInvoiceValidator
{
    public function __construct(private readonly Validator $validator) {}
    public function validate(array $input):SupplierInvoiceData
    {
        $errors=[];$vendor=$this->id($input['vendor_id']??null);$project=$this->id($input['project_id']??null);$purchaseOrder=$this->optionalId($input['purchase_order_id']??null);$number=trim((string)($input['invoice_number']??''));$date=$this->date((string)($input['invoice_date']??''),false);$due=$this->date((string)($input['due_date']??''),true);
        if($vendor===null)$errors['vendor_id'][]='المورد غير صالح.';if($project===null)$errors['project_id'][]='المشروع غير صالح.';if(trim((string)($input['purchase_order_id']??''))!==''&&$purchaseOrder===null)$errors['purchase_order_id'][]='أمر الشراء غير صالح.';if($number===''||mb_strlen($number)>100)$errors['invoice_number'][]='رقم فاتورة المورد مطلوب وبحد أقصى 100 حرف.';if($date===null)$errors['invoice_date'][]='تاريخ الفاتورة غير صالح.';if(trim((string)($input['due_date']??''))!==''&&$due===null)$errors['due_date'][]='تاريخ الاستحقاق غير صالح.';if($date!==null&&$due!==null&&$due<$date)$errors['due_date'][]='تاريخ الاستحقاق يجب ألا يسبق تاريخ الفاتورة.';
        $lines=[];foreach((array)($input['lines']??[]) as $line){$description=trim((string)($line['description']??''));$code=$this->id($line['cost_code_id']??null);$amount=$this->decimal((string)($line['amount']??''));if($description===''||mb_strlen($description)>500||$code===null||$amount===null){$errors['lines'][]='يرجى استكمال وصف وكود ومبلغ كل بند بصورة صحيحة.';continue;}$lines[]=['description'=>$description,'cost_code_id'=>$code,'amount'=>$amount,'sort_order'=>count($lines)+1];}
        if($lines===[])$errors['lines'][]='يجب إضافة بند واحد على الأقل.';if(!$this->validator->validate($input,['reference'=>'string|max:190','notes'=>'string|max:5000']))$errors=array_merge_recursive($errors,$this->validator->errors());if($errors)throw new ValidationException($errors);$nullable=static fn(mixed $value):?string=>trim((string)$value)===''?null:trim((string)$value);
        return new SupplierInvoiceData((int)$vendor,(int)$project,$purchaseOrder,$number,(string)$date,$due,$nullable($input['reference']??''),$nullable($input['notes']??''),$lines);
    }
    private function id(mixed $value):?int{$id=filter_var($value,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);return $id===false?null:$id;}
    private function optionalId(mixed $value):?int{return trim((string)$value)===''?null:$this->id($value);}
    private function date(string $value,bool $optional):?string{$value=trim($value);if($optional&&$value==='')return null;$date=\DateTimeImmutable::createFromFormat('!Y-m-d',$value);return $date&&$date->format('Y-m-d')===$value?$value:null;}
    private function decimal(string $value):?string{$value=trim($value);if(!preg_match('/^\d{1,16}(?:\.\d{1,2})?$/',$value))return null;[$whole,$fraction]=array_pad(explode('.',$value,2),2,'');$whole=ltrim($whole,'0');$normalized=($whole===''?'0':$whole).'.'.str_pad($fraction,2,'0');return $normalized==='0.00'?null:$normalized;}
}
