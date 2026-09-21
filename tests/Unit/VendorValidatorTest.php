<?php
declare(strict_types=1);
use App\Core\Exceptions\ValidationException;
use App\Core\Validation\Validator;
use App\Modules\Vendors\Validators\VendorValidator;
$validator=new VendorValidator(new Validator());
foreach(['supplier','subcontractor','both'] as $type){$input=['vendor_type'=>$type,'name'=>'Vendor','email'=>''];if($type!=='supplier'){$input['work_section_ids']=['1','2','2'];}$data=$validator->validate($input);if($data->vendorType!==$type||$data->email!==null||($type!=='supplier'&&$data->workSectionIds!==[1,2])){throw new RuntimeException('Vendor validation or normalization failed.');}}
foreach([['vendor_type'=>'invalid','name'=>'Vendor'],['vendor_type'=>'supplier','name'=>''],['vendor_type'=>'supplier','name'=>'Vendor','email'=>'invalid']] as $invalid){try{$validator->validate($invalid);throw new RuntimeException('Invalid vendor data was accepted.');}catch(ValidationException){}}
foreach([['vendor_type'=>'subcontractor','name'=>'Vendor'],['vendor_type'=>'both','name'=>'Vendor','work_section_ids'=>['invalid']]] as $invalid){try{$validator->validate($invalid);throw new RuntimeException('Vendor without valid work sections was accepted.');}catch(ValidationException){}}
