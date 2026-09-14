<?php

declare(strict_types=1);

namespace App\Modules\Clients\Validators;

use App\Core\Exceptions\ValidationException;
use App\Core\Validation\Validator;
use App\Modules\Clients\DTOs\ClientData;

final class ClientValidator
{
    public function __construct(private readonly Validator $validator) {}

    /** @param array<string, mixed> $input */
    public function validate(array $input): ClientData
    {
        if (!$this->validator->validate($input, [
            'client_type' => 'required|string|max:20',
            'name' => 'string|max:190',
            'company_name' => 'string|max:190',
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

        $type = trim((string) ($input['client_type'] ?? ''));
        $name = $this->nullable($input['name'] ?? null);
        $companyName = $this->nullable($input['company_name'] ?? null);
        $email = $this->nullable($input['email'] ?? null);
        $errors = [];
        if (!in_array($type, ['individual', 'company'], true)) {
            $errors['client_type'][] = 'نوع العميل غير صالح.';
        }
        if ($type === 'individual' && $name === null) {
            $errors['name'][] = 'اسم العميل مطلوب للأفراد.';
        }
        if ($type === 'company' && $companyName === null) {
            $errors['company_name'][] = 'اسم الشركة مطلوب لعملاء الشركات.';
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

        return new ClientData(
            $type,
            $name,
            $companyName,
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
