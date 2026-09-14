<?php

declare(strict_types=1);

namespace App\Core\Validation;

final class Validator
{
    /** @var array<string, list<string>> */
    private array $errors = [];

    /** @param array<string, mixed> $data @param array<string, string> $rules */
    public function validate(array $data, array $rules): bool
    {
        $this->errors = [];
        foreach ($rules as $field => $ruleLine) {
            $value = $data[$field] ?? null;
            foreach (explode('|', $ruleLine) as $rule) {
                [$name, $parameter] = array_pad(explode(':', $rule, 2), 2, null);
                if ($name === 'required' && ($value === null || trim((string) $value) === '')) { $this->errors[$field][] = 'هذا الحقل مطلوب.'; }
                if ($name === 'string' && $value !== null && !is_string($value)) { $this->errors[$field][] = 'يجب أن تكون القيمة نصًا.'; }
                if ($name === 'max' && $value !== null && mb_strlen((string) $value) > (int) $parameter) { $this->errors[$field][] = "يجب ألا تتجاوز القيمة {$parameter} حرفًا."; }
            }
        }
        return $this->errors === [];
    }

    /** @return array<string, list<string>> */
    public function errors(): array { return $this->errors; }
}
