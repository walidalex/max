<?php
declare(strict_types=1);
namespace App\Modules\Employees\Validators;
use App\Core\Exceptions\ValidationException;
use App\Modules\Employees\DTOs\EmployeeData;
final class EmployeeValidator{public function validate(array$i):EmployeeData{$e=[];$name=trim((string)($i['name']??''));$phone=$this->nullable($i['phone']??null);$email=$this->nullable($i['email']??null);if($name===''||mb_strlen($name)>190)$e['name'][]='اسم الموظف مطلوب وبحد أقصى 190 حرفًا.';if($phone!==null&&mb_strlen($phone)>40)$e['phone'][]='رقم الهاتف يتجاوز الحد المسموح.';if($email!==null&&(mb_strlen($email)>190||filter_var($email,FILTER_VALIDATE_EMAIL)===false))$e['email'][]='البريد الإلكتروني غير صالح.';if($e)throw new ValidationException($e);return new EmployeeData($name,$phone,$email,isset($i['is_active']));}private function nullable(mixed$v):?string{$v=trim((string)$v);return$v===''?null:$v;}}
