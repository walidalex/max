<?php
declare(strict_types=1);
namespace App\Modules\SubcontractPayments\Validators;
use App\Core\Exceptions\ValidationException;
use App\Modules\SubcontractPayments\DTOs\SubcontractPaymentData;
final class SubcontractPaymentValidator
{
    public const METHODS = ['cash','bank_transfer','cheque','other'];
    private const BANK_METHODS = ['bank_transfer','cheque','instapay','other'];
    public function validate(array $input,int $subcontractId):SubcontractPaymentData
    {
        $errors=[];
        $date=$this->date($input['payment_date']??null);
        if(!is_string($date))$errors['payment_date'][]='تاريخ الدفع غير صالح.';
        $amount=trim((string)($input['amount']??''));
        if(!preg_match('/^(?=.{1,19}$)(?:0*[1-9]\d{0,15})(?:\.\d{1,2})?$|^0*\.\d{1,2}$/',$amount)||preg_match('/^0*(?:\.0{1,2})?$/',$amount))$errors['amount'][]='مبلغ الدفعة يجب أن يكون أكبر من صفر وبحد أقصى 16 رقمًا صحيحًا ودقتين عشريتين.';
        $channel=(string)($input['payment_channel']??'');
        if(!in_array($channel,['cash','bank'],true))$errors['payment_channel'][]='اختر طريقة الدفع نقدي أو بنكي.';
        $bankMethod=$channel==='bank'?(string)($input['bank_payment_method']??''):null;
        if($channel==='bank'&&!in_array($bankMethod,self::BANK_METHODS,true))$errors['bank_payment_method'][]='اختر وسيلة العملية البنكية.';
        $account=filter_var($input['payment_account_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        if($account===false)$errors['payment_account_id'][]='اختر حساب الدفع.';
        $reference=$this->text($input['reference_number']??null,190,$errors,'reference_number');
        if($channel==='bank'&&$reference===null)$errors['reference_number'][]='الرقم المرجعي مطلوب للعملية البنكية.';
        $chequeNumber=$this->text($input['cheque_number']??null,100,$errors,'cheque_number');
        $chequeDate=$this->date($input['cheque_date']??null,true);
        $chequeDueDate=$this->date($input['cheque_due_date']??null,true);
        if($bankMethod==='cheque'){if($chequeNumber===null)$errors['cheque_number'][]='رقم الشيك مطلوب.';if($chequeDate===null)$errors['cheque_date'][]='تاريخ الشيك مطلوب.';}
        if($chequeDate===false)$errors['cheque_date'][]='تاريخ الشيك غير صالح.';
        if($chequeDueDate===false)$errors['cheque_due_date'][]='تاريخ الاستحقاق غير صالح.';
        $notes=$this->text($input['notes']??null,5000,$errors,'notes');
        if($errors)throw new ValidationException($errors);
        [$whole,$fraction]=array_pad(explode('.',ltrim($amount,'0')?:'0',2),2,'');
        return new SubcontractPaymentData($subcontractId,(string)$date,($whole===''?'0':$whole).'.'.str_pad($fraction,2,'0'),$channel,$bankMethod,(int)$account,$reference,$bankMethod==='cheque'?$chequeNumber:null,$bankMethod==='cheque'&&is_string($chequeDate)?$chequeDate:null,$bankMethod==='cheque'&&is_string($chequeDueDate)?$chequeDueDate:null,$notes);
    }
    private function date(mixed $value,bool $nullable=false):string|false|null{$value=trim((string)$value);if($value==='')return$nullable?null:false;$date=\DateTimeImmutable::createFromFormat('!Y-m-d',$value);return$date&&$date->format('Y-m-d')===$value?$value:false;}
    private function text(mixed$value,int$max,array&$errors,string$key):?string{$value=trim((string)$value);if($value==='')return null;if(mb_strlen($value)>$max)$errors[$key][]='القيمة أطول من المسموح.';return$value;}
}
