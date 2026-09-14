<?php
declare(strict_types=1);
namespace App\Modules\Projects\Validators;
use App\Core\Exceptions\ValidationException;
use App\Core\Validation\Validator;
use App\Modules\Projects\DTOs\ProjectData;
final class ProjectValidator
{
 public const TYPES=['interior_design','fit_out','contracting','renovation','maintenance','other'];
 public const STATUSES=['planning','active','on_hold','completed','cancelled'];
 public function __construct(private readonly Validator $validator){}
 public function validate(array $input):ProjectData
 {
  if(!$this->validator->validate($input,['name'=>'required|string|max:190','client_id'=>'required','project_type'=>'required|string|max:20','status'=>'required|string|max:20','site_address'=>'string|max:500','city'=>'string|max:120','area'=>'string|max:120','description'=>'string|max:5000','notes'=>'string|max:5000']))throw new ValidationException($this->validator->errors());
  $errors=[];$clientId=$this->positive($input['client_id']??null);$contactId=$this->optionalId($input['primary_contact_id']??null);$managerId=$this->optionalId($input['project_manager_id']??null);
  if($clientId===null)$errors['client_id'][]='العميل المحدد غير صالح.';
  foreach(['primary_contact_id'=>$contactId,'project_manager_id'=>$managerId] as $field=>$id){if(trim((string)($input[$field]??''))!==''&&$id===null)$errors[$field][]='القيمة المحددة غير صالحة.';}
  $type=trim((string)($input['project_type']??''));$status=trim((string)($input['status']??''));
  if(!in_array($type,self::TYPES,true))$errors['project_type'][]='نوع المشروع غير صالح.';
  if(!in_array($status,self::STATUSES,true))$errors['status'][]='حالة المشروع غير صالحة.';
  $dates=[];foreach(['start_date','expected_end_date','actual_end_date'] as $field){$dates[$field]=$this->date($input[$field]??null);if(trim((string)($input[$field]??''))!==''&&$dates[$field]===null)$errors[$field][]='التاريخ غير صالح.';}
  if($dates['start_date']!==null&&$dates['expected_end_date']!==null&&$dates['expected_end_date']<$dates['start_date'])$errors['expected_end_date'][]='تاريخ الانتهاء المتوقع لا يمكن أن يسبق تاريخ البدء.';
  if($errors!==[])throw new ValidationException($errors);
  return new ProjectData(trim((string)$input['name']),(int)$clientId,$contactId,$managerId,$type,$status,$dates['start_date'],$dates['expected_end_date'],$dates['actual_end_date'],$this->nullable($input['site_address']??null),$this->nullable($input['city']??null),$this->nullable($input['area']??null),$this->nullable($input['description']??null),$this->nullable($input['notes']??null));
 }
 private function nullable(mixed $v):?string{$v=trim((string)$v);return $v===''?null:$v;}
 private function positive(mixed $v):?int{$i=filter_var($v,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);return $i===false?null:$i;}
 private function optionalId(mixed $v):?int{return trim((string)$v)===''?null:$this->positive($v);}
 private function date(mixed $v):?string{$v=trim((string)$v);if($v==='')return null;$d=\DateTimeImmutable::createFromFormat('!Y-m-d',$v);$e=\DateTimeImmutable::getLastErrors();return $d!==false&&($e===false||($e['warning_count']===0&&$e['error_count']===0))&&$d->format('Y-m-d')===$v?$v:null;}
}
