<?php
declare(strict_types=1);
namespace App\Modules\Projects\Validators;
use App\Core\Exceptions\ValidationException;
final class ProjectStatusValidator
{
 public function validate(mixed $value):string
 {
  $status=trim((string)$value);
  if(!in_array($status,ProjectValidator::STATUSES,true))throw new ValidationException(['status'=>['حالة المشروع غير صالحة.']]);
  return $status;
 }
}
