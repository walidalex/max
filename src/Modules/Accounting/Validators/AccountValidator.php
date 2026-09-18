<?php
declare(strict_types=1);
namespace App\Modules\Accounting\Validators;
use App\Core\Exceptions\ValidationException;
use App\Modules\Accounting\DTOs\AccountData;
final class AccountValidator
{
    public function validate(array $input):AccountData
    {
        $errors=[];$code=trim((string)($input['account_code']??''));$nameAr=trim((string)($input['name_ar']??''));$nameEn=trim((string)($input['name_en']??''));$type=(string)($input['account_type']??'');$balance=(string)($input['normal_balance']??'');$parent=$this->optionalId($input['parent_id']??null);
        if(!preg_match('/^\d{6}$/D',$code))$errors['account_code'][]='كود الحساب يجب أن يتكون من 6 أرقام بالضبط.';
        if($nameAr===''||mb_strlen($nameAr)>190)$errors['name_ar'][]='اسم الحساب العربي مطلوب وبحد أقصى 190 حرفًا.';
        if($nameEn===''||mb_strlen($nameEn)>190)$errors['name_en'][]='اسم الحساب الإنجليزي مطلوب وبحد أقصى 190 حرفًا.';
        if(!in_array($type,['asset','liability','equity','revenue','expense'],true))$errors['account_type'][]='نوع الحساب غير صالح.';
        if(!in_array($balance,['debit','credit'],true))$errors['normal_balance'][]='طبيعة الحساب غير صالحة.';
        if(trim((string)($input['parent_id']??''))!==''&&$parent===null)$errors['parent_id'][]='الحساب الأب غير صالح.';
        if($errors)throw new ValidationException($errors);
        return new AccountData($code,$nameAr,$nameEn,$parent,$type,$balance,isset($input['is_postable']),isset($input['is_control']),isset($input['is_active']));
    }
    private function optionalId(mixed $value):?int{if(trim((string)$value)==='')return null;$id=filter_var($value,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);return$id===false?null:$id;}
}
