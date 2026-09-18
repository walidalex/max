<?php
declare(strict_types=1);
namespace App\Modules\EmployeeCustodies\Validators;
use App\Core\Exceptions\ValidationException;
use App\Modules\EmployeeCustodies\DTOs\CustodyData;
final class CustodyValidator
{
 public function validate(array$i):CustodyData{$e=[];$employee=$this->id($i['employee_id']??null);$project=$this->optionalId($i['project_id']??null);$date=$this->date($i['issue_date']??null);$amount=$this->decimal($i['amount']??null);$type=(string)($i['funding_type']??'');$account=$this->id($i['funding_account_id']??null);$purpose=$this->nullable($i['purpose']??null);if($employee===null)$e['employee_id'][]='الموظف غير صالح.';if(trim((string)($i['project_id']??''))!==''&&$project===null)$e['project_id'][]='المشروع غير صالح.';if($date===null)$e['issue_date'][]='تاريخ إصدار العهدة غير صالح.';if($amount===null)$e['amount'][]='مبلغ العهدة غير صالح أو يتجاوز الحد المسموح.';if(!in_array($type,['cash','bank'],true))$e['funding_type'][]='نوع التمويل غير صالح.';if($account===null)$e['funding_account_id'][]='حساب التمويل غير صالح.';if($purpose!==null&&mb_strlen($purpose)>500)$e['purpose'][]='الغرض يتجاوز 500 حرف.';if($e)throw new ValidationException($e);return new CustodyData((int)$employee,$project,(string)$date,(string)$amount,$type,(int)$account,$purpose);}
 private function decimal(mixed$v):?string{$v=trim((string)$v);if(!preg_match('/^\d{1,16}(?:\.\d{1,2})?$/D',$v))return null;[$w,$f]=array_pad(explode('.',$v,2),2,'');$w=ltrim($w,'0');$n=($w===''?'0':$w).'.'.str_pad($f,2,'0');return$n==='0.00'?null:$n;}private function id(mixed$v):?int{$x=filter_var($v,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);return$x===false?null:$x;}private function optionalId(mixed$v):?int{return trim((string)$v)===''?null:$this->id($v);}private function date(mixed$v):?string{$v=trim((string)$v);$d=\DateTimeImmutable::createFromFormat('!Y-m-d',$v);return$d&&$d->format('Y-m-d')===$v?$v:null;}private function nullable(mixed$v):?string{$v=trim((string)$v);return$v===''?null:$v;}
}
