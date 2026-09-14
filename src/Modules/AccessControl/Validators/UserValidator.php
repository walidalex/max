<?php
declare(strict_types=1);
namespace App\Modules\AccessControl\Validators;
use App\Core\Exceptions\ValidationException;
use App\Core\Validation\Validator;
use App\Modules\AccessControl\DTOs\UserData;
final class UserValidator
{
    public function __construct(private readonly Validator $validator) {}
    /** @param array<string,mixed> $input */
    public function validate(array $input, bool $creating): UserData
    {
        $rules=['username'=>'required|string|max:60','name'=>'required|string|max:120'];
        if ($creating) { $rules['password']='required|string|max:255'; }
        if (!$this->validator->validate($input,$rules)) { throw new ValidationException($this->validator->errors()); }
        $username=trim((string)$input['username']); $email=trim((string)($input['email']??'')); $password=(string)($input['password']??'');
        $errors=[];
        if (!preg_match('/^[A-Za-z0-9_.-]{3,60}$/',$username)) { $errors['username'][]='اسم المستخدم يقبل الحروف الإنجليزية والأرقام و . _ - فقط.'; }
        if ($email!=='' && filter_var($email,FILTER_VALIDATE_EMAIL)===false) { $errors['email'][]='البريد الإلكتروني غير صالح.'; }
        if (($creating || $password!=='') && strlen($password)<8) { $errors['password'][]='كلمة المرور يجب ألا تقل عن 8 أحرف.'; }
        if ($errors!==[]) { throw new ValidationException($errors); }
        $ids=array_values(array_unique(array_filter(array_map('intval',(array)($input['role_ids']??[])),static fn(int $id):bool=>$id>0)));
        return new UserData($username,trim((string)$input['name']),$email===''?null:$email,$password===''?null:$password,$ids);
    }
}
