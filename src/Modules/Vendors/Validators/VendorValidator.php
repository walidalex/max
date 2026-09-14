<?php

declare(strict_types=1);

namespace App\Modules\Vendors\Validators;

use App\Core\Exceptions\ValidationException;
use App\Core\Validation\Validator;
use App\Modules\Vendors\DTOs\VendorData;

final class VendorValidator
{
    public function __construct(private readonly Validator $validator) {}

    /** @param array<string, mixed> $input */
    public function validate(array $input): VendorData
    {
        if (!$this->validator->validate($input, [
            'vendor_type' => 'required|string|max:20',
            'name' => 'required|string|max:190',
            'tax_number' => 'string|max:80',
            'commercial_registration' => 'string|max:80',
            'phone' => 'string|max:30',
            'mobile' => 'string|max:30',
            'email' => 'string|max:190',
            'address' => 'string|max:500',
            'notes' => 'string|max:5000',
        ])) {
            throw new ValidationException($this->validator->errors());
        }

        $type = trim((string) ($input['vendor_type'] ?? ''));
        $name = $this->nullable($input['name'] ?? null);
        $email = $this->nullable($input['email'] ?? null);
        $errors = [];
        if (!in_array($type, ['supplier', 'subcontractor', 'both'], true)) {
            $errors['vendor_type'][] = 'نوع المورد غير صالح.';
        }
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

        return new VendorData(
            $type,
            (string) $name,
            $this->nullable($input['tax_number'] ?? null),
            $this->nullable($input['commercial_registration'] ?? null),
            $this->nullable($input['phone'] ?? null),
            $this->nullable($input['mobile'] ?? null),
            $email,
            $this->nullable($input['address'] ?? null),
            $this->nullable($input['notes'] ?? null),
        );
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }
}
