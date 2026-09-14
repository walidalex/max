<?php

declare(strict_types=1);

namespace App\Modules\Clients\Validators;

use App\Core\Exceptions\ValidationException;
use App\Core\Validation\Validator;
use App\Modules\Clients\DTOs\ContactData;

final class ContactValidator
{
    public function __construct(private readonly Validator $validator) {}

    /** @param array<string, mixed> $input */
    public function validate(array $input): ContactData
    {
        if (!$this->validator->validate($input, [
            'name' => 'required|string|max:190',
            'job_title' => 'string|max:120',
            'phone' => 'string|max:30',
            'mobile' => 'string|max:30',
            'email' => 'string|max:190',
            'notes' => 'string|max:5000',
        ])) {
            throw new ValidationException($this->validator->errors());
        }
        $email = $this->nullable($input['email'] ?? null);
        $errors = [];
        if ($email !== null && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'][] = 'البريد الإلكتروني غير صالح.';
        }
        foreach (['phone', 'mobile'] as $field) {
            $value = $this->nullable($input[$field] ?? null);
            if ($value !== null && !preg_match('/^[0-9+()\-\s]{3,30}$/', $value)) {
                $errors[$field][] = 'رقم الهاتف يحتوي على أحرف غير صالحة.';
            }
        }
        if ($errors !== []) {
            throw new ValidationException($errors);
        }
        return new ContactData(
            trim((string) $input['name']),
            $this->nullable($input['job_title'] ?? null),
            $this->nullable($input['phone'] ?? null),
            $this->nullable($input['mobile'] ?? null),
            $email,
            (bool) ($input['is_primary'] ?? false),
            $this->nullable($input['notes'] ?? null),
        );
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }
}
