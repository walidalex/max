<?php
declare(strict_types=1);
namespace App\Modules\AccessControl\Validators;
use App\Core\Exceptions\ValidationException;
use App\Core\Validation\Validator;
use App\Modules\AccessControl\DTOs\RoleData;
final class RoleValidator
{
    public function __construct(private readonly Validator $validator) {}
    /** @param array<string,mixed> $input */
    public function validate(array $input): RoleData
    {
        if (!$this->validator->validate($input,['name'=>'required|string|max:100','code'=>'required|string|max:80','description'=>'string|max:255'])) { throw new ValidationException($this->validator->errors()); }
        $code=trim((string)$input['code']);
        if (!preg_match('/^[a-z][a-z0-9_]{2,79}$/',$code)) { throw new ValidationException(['code'=>['الكود يقبل حروفًا إنجليزية صغيرة وأرقامًا وشرطة سفلية فقط.']]); }
        $description=trim((string)($input['description']??''));
        return new RoleData(trim((string)$input['name']),$code,$description===''?null:$description,isset($input['is_active']));
    }
}
