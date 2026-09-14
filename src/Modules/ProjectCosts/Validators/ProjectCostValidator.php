<?php
declare(strict_types=1);
namespace App\Modules\ProjectCosts\Validators;
use App\Core\Exceptions\ValidationException;use App\Core\Validation\Validator;use App\Modules\ProjectCosts\DTOs\ProjectCostData;
final class ProjectCostValidator{public function __construct(private readonly Validator $validator){}
 public function validate(array $input):ProjectCostData{
  if(!$this->validator->validate($input,['project_id'=>'required','cost_date'=>'required|string|max:10','cost_code_id'=>'required','description'=>'required|string|max:500','amount'=>'required|string|max:21','reference_number'=>'string|max:190','notes'=>'string|max:5000']))throw new ValidationException($this->validator->errors());
  $errors=[];$date=trim((string)($input['cost_date']??''));$amount=trim((string)($input['amount']??''));$project=(int)($input['project_id']??0);$code=(int)($input['cost_code_id']??0);
  $parsed=\DateTimeImmutable::createFromFormat('!Y-m-d',$date);if(!$parsed||$parsed->format('Y-m-d')!==$date)$errors['cost_date'][]='تاريخ التكلفة غير صالح.';
  if(!preg_match('/^\d{1,16}(?:\.\d{1,2})?$/',$amount)||preg_match('/^0+(?:\.0{1,2})?$/',$amount))$errors['amount'][]='يجب أن يكون المبلغ أكبر من صفر.';
  if($project<1)$errors['project_id'][]='المشروع مطلوب.';if($code<1)$errors['cost_code_id'][]='كود التكلفة مطلوب.';if($errors)throw new ValidationException($errors);
  $nullable=static fn(mixed $v):?string=>trim((string)$v)===''?null:trim((string)$v);
  return new ProjectCostData($project,$date,$code,(int)($input['vendor_id']??0)?:null,trim((string)$input['description']),$nullable($input['reference_number']??''),$amount,$nullable($input['notes']??''));
 }}
