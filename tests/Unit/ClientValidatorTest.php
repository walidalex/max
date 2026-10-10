<?php

declare(strict_types=1);

use App\Core\Exceptions\ValidationException;
use App\Core\Validation\Validator;
use App\Modules\Clients\Validators\ClientValidator;

$validator = new ClientValidator(new Validator());
$individual = $validator->validate(['client_type' => 'individual', 'name' => 'عميل تجريبي', 'national_id' => '29801011234567', 'company_name' => 'تجاهل', 'tax_number' => 'تجاهل', 'commercial_registration' => 'تجاهل', 'email' => '']);
if ($individual->name !== 'عميل تجريبي' || $individual->nationalId !== '29801011234567' || $individual->email !== null || $individual->companyName !== null || $individual->taxNumber !== null || $individual->commercialRegistration !== null) {
    throw new RuntimeException('Individual client validation or normalization failed.');
}
$company = $validator->validate(['client_type' => 'company', 'name' => 'تجاهل', 'company_name' => 'شركة تجريبية', 'national_id' => 'تجاهل', 'email' => 'info@example.com']);
if ($company->companyName !== 'شركة تجريبية' || $company->name !== null || $company->nationalId !== null) {
    throw new RuntimeException('Company client validation failed.');
}
foreach ([
    ['client_type' => 'vendor', 'name' => 'Invalid'],
    ['client_type' => 'individual', 'name' => ''],
    ['client_type' => 'company', 'company_name' => '', 'email' => 'invalid'],
] as $invalid) {
    try {
        $validator->validate($invalid);
        throw new RuntimeException('Invalid client data was accepted.');
    } catch (ValidationException) {
    }
}
