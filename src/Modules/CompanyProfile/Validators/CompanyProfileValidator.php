<?php
declare(strict_types=1);
namespace App\Modules\CompanyProfile\Validators;
use App\Core\Exceptions\ValidationException;
use App\Core\Http\UploadedFile;
use App\Core\Validation\Validator;
use App\Modules\CompanyProfile\DTOs\CompanyProfileData;
final class CompanyProfileValidator
{
    private const MIMES=['image/png','image/jpeg','image/webp'];
    public function __construct(private readonly Validator $validator) {}
    /** @param array<string,mixed> $input */
    public function validate(array $input,?UploadedFile $logo):CompanyProfileData
    {
        $rules=['name'=>'required|string|max:150','legal_name'=>'string|max:190','tax_number'=>'string|max:80','commercial_registration'=>'string|max:80','phone'=>'string|max:30','mobile'=>'string|max:30','email'=>'string|max:190','website'=>'string|max:255','address'=>'string|max:500'];
        if(!$this->validator->validate($input,$rules)){throw new ValidationException($this->validator->errors());}
        $values=[];foreach(['legal_name','tax_number','commercial_registration','phone','mobile','email','website','address'] as $field){$value=trim((string)($input[$field]??''));$values[$field]=$value===''?null:$value;}
        $errors=[];
        if($values['email']!==null&&filter_var($values['email'],FILTER_VALIDATE_EMAIL)===false){$errors['email'][]='البريد الإلكتروني غير صالح.';}
        if($values['website']!==null&&(filter_var($values['website'],FILTER_VALIDATE_URL)===false||!in_array(parse_url($values['website'],PHP_URL_SCHEME),['http','https'],true))){$errors['website'][]='الموقع يجب أن يكون رابط HTTP أو HTTPS صالحًا.';}
        foreach(['phone','mobile'] as $field){if($values[$field]!==null&&!preg_match('/^[0-9+()\-\s]{3,30}$/',$values[$field])){$errors[$field][]='رقم الهاتف يحتوي على رموز غير مسموحة.';}}
        if($logo?->isPresent()){$this->validateLogo($logo,$errors);}
        if($errors!==[]){throw new ValidationException($errors);}
        return new CompanyProfileData(trim((string)$input['name']),$values['legal_name'],$values['tax_number'],$values['commercial_registration'],$values['phone'],$values['mobile'],$values['email'],$values['website'],$values['address'],$logo?->isPresent()?$logo:null);
    }
    /** @param array<string,list<string>> $errors */
    private function validateLogo(UploadedFile $logo,array &$errors):void
    {
        if(!$logo->isValid()){$errors['logo'][]='فشل رفع ملف الشعار.';return;}
        if($logo->size()>2*1024*1024){$errors['logo'][]='حجم الشعار يجب ألا يتجاوز 2 MB.';}
        if(!in_array($logo->mimeType(),self::MIMES,true)){$errors['logo'][]='صيغة الشعار يجب أن تكون PNG أو JPEG أو WebP.';}
        $dimensions=$logo->dimensions();if($dimensions===null){$errors['logo'][]='الملف ليس صورة صالحة.';}elseif($dimensions[0]>4000||$dimensions[1]>4000){$errors['logo'][]='أبعاد الشعار يجب ألا تتجاوز 4000 × 4000.';}
    }
}
