<?php
declare(strict_types=1);
use App\Core\Exceptions\ValidationException;
use App\Core\Validation\Validator;
use App\Modules\Vendors\Validators\VendorValidator;
$validator=new VendorValidator(new Validator());
foreach(['supplier','subcontractor','both'] as $type){$input=['vendor_type'=>$type,'name'=>'Vendor','email'=>'','bank_name'=>'nbe','bank_account'=>'EG123','instapay_number'=>'01000000000','governorate'=>'all'];if($type!=='supplier'){$input['work_section_ids']=['1','2','2'];}$data=$validator->validate($input);if($data->vendorType!==$type||$data->email!==null||$data->bankName!=='nbe'||$data->bankAccount!=='EG123'||$data->instapayNumber!=='01000000000'||$data->governorate!=='all'||($type!=='supplier'&&$data->workSectionIds!==[1,2])){throw new RuntimeException('Vendor validation or normalization failed.');}}
foreach([['vendor_type'=>'invalid','name'=>'Vendor'],['vendor_type'=>'supplier','name'=>''],['vendor_type'=>'supplier','name'=>'Vendor','email'=>'invalid'],['vendor_type'=>'supplier','name'=>'Vendor','bank_name'=>'invalid'],['vendor_type'=>'supplier','name'=>'Vendor','governorate'=>'invalid']] as $invalid){try{$validator->validate($invalid);throw new RuntimeException('Invalid vendor data was accepted.');}catch(ValidationException){}}
foreach([['vendor_type'=>'subcontractor','name'=>'Vendor'],['vendor_type'=>'both','name'=>'Vendor','work_section_ids'=>['invalid']]] as $invalid){try{$validator->validate($invalid);throw new RuntimeException('Vendor without valid work sections was accepted.');}catch(ValidationException){}}
