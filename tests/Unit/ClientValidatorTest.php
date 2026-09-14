<?php

declare(strict_types=1);

use App\Core\Exceptions\ValidationException;
use App\Core\Validation\Validator;
use App\Modules\Clients\Validators\ClientValidator;

$validator = new ClientValidator(new Validator());
$individual = $validator->validate(['client_type' => 'individual', 'name' => 'عميل تجريبي', 'email' => '']);
if ($individual->name !== 'عميل تجريبي' || $individual->email !== null || $individual->companyName !== null) {
    throw new RuntimeException('Individual client validation or normalization failed.');
}
$company = $validator->validate(['client_type' => 'company', 'company_name' => 'شركة تجريبية', 'email' => 'info@example.com']);
if ($company->companyName !== 'شركة تجريبية') {
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
