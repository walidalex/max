<?php
declare(strict_types=1);
namespace App\Modules\Accounting\Validators;
use App\Core\Exceptions\ValidationException;
use App\Modules\Accounting\DTOs\JournalData;
final class JournalValidator
{
    public function validate(array $input):JournalData
    {
        $errors=[];$date=$this->date($input['journal_date']??null);$description=trim((string)($input['description']??''));
        if($date===null)$errors['journal_date'][]='تاريخ القيد غير صالح.';
        if($description===''||mb_strlen($description)>500)$errors['description'][]='وصف القيد مطلوب وبحد أقصى 500 حرف.';
        $lines=[];
        foreach(array_slice((array)($input['lines']??[]),0,200) as $line){$account=$this->id($line['account_id']??null);$lineDescription=trim((string)($line['description']??''));$debit=$this->decimal($line['debit']??'0');$credit=$this->decimal($line['credit']??'0');if($account===null||$lineDescription===''||mb_strlen($lineDescription)>500||$debit===null||$credit===null||(($debit==='0.00')===($credit==='0.00'))){$errors['lines'][]='كل سطر يحتاج حسابًا ووصفًا وطرفًا مدينًا أو دائنًا واحدًا فقط.';continue;}$lines[]=['account_id'=>$account,'description'=>$lineDescription,'debit'=>$debit,'credit'=>$credit,'project_id'=>$this->optionalId($line['project_id']??null),'client_id'=>$this->optionalId($line['client_id']??null),'vendor_id'=>$this->optionalId($line['vendor_id']??null),'subcontract_id'=>$this->optionalId($line['subcontract_id']??null),'employee_id'=>$this->optionalId($line['employee_id']??null),'cost_code_id'=>$this->optionalId($line['cost_code_id']??null),'sort_order'=>count($lines)+1];}
        if(count($lines)<2)$errors['lines'][]='يجب إدخال سطرين على الأقل.';
        $reference=trim((string)($input['reference']??''));if(mb_strlen($reference)>190)$errors['reference'][]='المرجع يتجاوز 190 حرفًا.';
        if($errors)throw new ValidationException($errors);
        return new JournalData((string)$date,$description,$reference===''?null:$reference,$lines);
    }
    private function decimal(mixed $value):?string{$value=trim((string)$value);if(!preg_match('/^\d{1,16}(?:\.\d{1,2})?$/D',$value))return null;[$whole,$fraction]=array_pad(explode('.',$value,2),2,'');$whole=ltrim($whole,'0');return($whole===''?'0':$whole).'.'.str_pad($fraction,2,'0');}
    private function id(mixed $value):?int{$id=filter_var($value,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);return$id===false?null:$id;}
    private function optionalId(mixed $value):?int{return trim((string)$value)===''?null:$this->id($value);}
    private function date(mixed $value):?string{$value=trim((string)$value);$date=\DateTimeImmutable::createFromFormat('!Y-m-d',$value);return$date&&$date->format('Y-m-d')===$value?$value:null;}
}
