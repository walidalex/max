<?php
declare(strict_types=1);
namespace App\Modules\CostStructure\Validators;
use App\Core\Exceptions\ValidationException;use App\Core\Validation\Validator;use App\Modules\CostStructure\DTOs\WorkSectionData;
final class WorkSectionValidator{public function __construct(private readonly Validator $validator){}public function validate(array $i):WorkSectionData{if(!$this->validator->validate($i,['section_code'=>'required|string|max:20','name'=>'required|string|max:190','description'=>'string|max:5000']))throw new ValidationException($this->validator->errors());return new WorkSectionData(trim((string)$i['section_code']),trim((string)$i['name']),$this->nullable($i['description']??null),max(0,(int)($i['sort_order']??0)));}private function nullable(mixed $v):?string{$v=trim((string)$v);return $v===''?null:$v;}}
