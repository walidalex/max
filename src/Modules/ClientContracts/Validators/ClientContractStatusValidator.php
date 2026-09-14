<?php
declare(strict_types=1);
namespace App\Modules\ClientContracts\Validators;
use App\Core\Exceptions\ValidationException;use App\Modules\ClientContracts\DTOs\ClientContractStatusData;
final class ClientContractStatusValidator { public const STATUSES=['draft','active','suspended','completed','cancelled'];public function validate(mixed $value):ClientContractStatusData{$status=trim((string)$value);if(!in_array($status,self::STATUSES,true))throw new ValidationException(['status'=>['حالة العقد غير صالحة.']]);return new ClientContractStatusData($status);} }
