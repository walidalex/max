<?php
declare(strict_types=1);
namespace App\Modules\CostStructure\Validators;
use App\Core\Exceptions\ValidationException;use App\Core\Validation\Validator;use App\Modules\CostStructure\DTOs\UnitData;
final class UnitValidator{public function __construct(private readonly Validator $validator){}public function validate(array $i):UnitData{if(!$this->validator->validate($i,['code'=>'required|string|max:50','name_ar'=>'required|string|max:100','symbol_ar'=>'string|max:30']))throw new ValidationException($this->validator->errors());$symbol=trim((string)($i['symbol_ar']??''));return new UnitData(trim((string)$i['code']),trim((string)$i['name_ar']),$symbol===''?null:$symbol,max(0,(int)($i['sort_order']??0)));}}
