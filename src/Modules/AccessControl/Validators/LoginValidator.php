<?php
declare(strict_types=1);
namespace App\Modules\AccessControl\Validators;
use App\Core\Exceptions\ValidationException;
use App\Core\Validation\Validator;
use App\Modules\AccessControl\DTOs\LoginData;
final class LoginValidator
{
    public function __construct(private readonly Validator $validator) {}
    /** @param array<string,mixed> $input */
    public function validate(array $input): LoginData
    {
        if (!$this->validator->validate($input, ['login'=>'required|string|max:190','password'=>'required|string|max:255'])) { throw new ValidationException($this->validator->errors()); }
        return new LoginData(trim((string)$input['login']), (string)$input['password']);
    }
}
