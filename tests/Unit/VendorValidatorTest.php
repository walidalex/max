<?php
declare(strict_types=1);
use App\Core\Exceptions\ValidationException;
use App\Core\Validation\Validator;
use App\Modules\Vendors\Validators\VendorValidator;
$validator=new VendorValidator(new Validator());
foreach(['supplier','subcontractor','both'] as $type){$data=$validator->validate(['vendor_type'=>$type,'name'=>'Vendor','email'=>'']);if($data->vendorType!==$type||$data->email!==null){throw new RuntimeException('Vendor validation or normalization failed.');}}
foreach([['vendor_type'=>'invalid','name'=>'Vendor'],['vendor_type'=>'supplier','name'=>''],['vendor_type'=>'supplier','name'=>'Vendor','email'=>'invalid']] as $invalid){try{$validator->validate($invalid);throw new RuntimeException('Invalid vendor data was accepted.');}catch(ValidationException){}}
