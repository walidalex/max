<?php

declare(strict_types=1);

namespace App\Modules\Foundation\Validators;

use App\Core\Exceptions\ValidationException;
use App\Core\Validation\Validator;
use App\Modules\Foundation\DTOs\FoundationCheckData;

final class FoundationCheckValidator
{
    public function __construct(private readonly Validator $validator) {}

    /** @param array<string, mixed> $input */
    public function validate(array $input): FoundationCheckData
    {
        if (!$this->validator->validate($input, ['label' => 'required|string|max:80'])) {
            throw new ValidationException($this->validator->errors());
        }
        return new FoundationCheckData(trim((string) $input['label']));
    }
}
