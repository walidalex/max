<?php
declare(strict_types=1);
namespace App\Modules\EmployeeCustodies\DTOs;
final readonly class CustodyData{public function __construct(public int$employeeId,public ?int$projectId,public string$issueDate,public string$amount,public string$fundingType,public int$fundingAccountId,public ?string$purpose){}}
