<?php
declare(strict_types=1);
use App\Core\Exceptions\ValidationException;
use App\Modules\SubcontractPayments\Validators\SubcontractPaymentValidator;
$validator = new SubcontractPaymentValidator();
$cash = $validator->validate(['payment_date'=>'2026-09-14','amount'=>'5000.5','payment_channel'=>'cash','payment_account_id'=>1], 1);
if ($cash->amount !== '5000.50' || $cash->legacyMethod() !== 'cash') throw new RuntimeException('Cash payment validation failed.');
$bank = $validator->validate(['payment_date'=>'2026-09-14','amount'=>'1.00','payment_channel'=>'bank','bank_payment_method'=>'instapay','payment_account_id'=>2,'reference_number'=>'REF-1'], 1);
if ($bank->bankPaymentMethod !== 'instapay' || $bank->legacyMethod() !== 'other') throw new RuntimeException('Bank payment validation failed.');
foreach (['0','-1','1.001','10000000000000000.00'] as $amount) {try {$validator->validate(['payment_date'=>'2026-09-14','amount'=>$amount,'payment_channel'=>'cash','payment_account_id'=>1],1);throw new RuntimeException('Invalid payment accepted.');} catch (ValidationException) {}}
try {$validator->validate(['payment_date'=>'2026-09-14','amount'=>'1.00','payment_channel'=>'bank','bank_payment_method'=>'cheque','payment_account_id'=>2],1);throw new RuntimeException('Cheque without required data accepted.');} catch (ValidationException) {}
