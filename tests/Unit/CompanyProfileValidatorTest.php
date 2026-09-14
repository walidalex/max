<?php
declare(strict_types=1);
use App\Core\Exceptions\ValidationException;
use App\Core\Validation\Validator;
use App\Modules\CompanyProfile\Validators\CompanyProfileValidator;

$validator=new CompanyProfileValidator(new Validator());
$data=$validator->validate(['name'=>'Example Company','email'=>'','website'=>'https://example.com','phone'=>'+20 100-200'],null);
if($data->email!==null||$data->website!=='https://example.com'){throw new RuntimeException('Company profile normalization failed.');}
try{$validator->validate(['name'=>'','email'=>'invalid','website'=>'javascript:alert(1)'],null);throw new RuntimeException('Invalid company profile was accepted.');}
catch(ValidationException){}
