<?php

declare(strict_types=1);

use App\Core\Exceptions\ValidationException;
use App\Core\Validation\Validator;
use App\Modules\Clients\Validators\ContactValidator;

$validator = new ContactValidator(new Validator());
$contact = $validator->validate(['name' => 'Contact', 'is_primary' => '1', 'email' => 'contact@example.com']);
if (!$contact->isPrimary || $contact->jobTitle !== null) {
    throw new RuntimeException('Contact validation or normalization failed.');
}
try {
    $validator->validate(['name' => '', 'email' => 'invalid']);
    throw new RuntimeException('Invalid contact data was accepted.');
} catch (ValidationException) {
}
