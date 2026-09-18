<?php
declare(strict_types=1);
namespace App\Modules\EmployeeCustodies\DTOs;
final readonly class SettlementData{public function __construct(public string$type,public string$date,public string$description,public string$amount,public ?int$projectId,public ?int$costCodeId,public ?int$returnAccountId,public ?string$reference){}}
