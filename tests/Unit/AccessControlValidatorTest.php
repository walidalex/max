<?php
declare(strict_types=1);
use App\Core\Exceptions\ValidationException;
use App\Core\Validation\Validator;
use App\Modules\AccessControl\Validators\UserValidator;

$validator=new UserValidator(new Validator());
$data=$validator->validate(['username'=>'admin_1','name'=>'مدير','email'=>'','password'=>'safe-password','role_ids'=>['1','1','0']],true);
if($data->email!==null||$data->roleIds!==[1]){throw new RuntimeException('User input normalization failed.');}
try{$validator->validate(['username'=>'اسم عربي','name'=>'Test','password'=>'short'],true);throw new RuntimeException('Invalid access-control input was accepted.');}
catch(ValidationException){}
