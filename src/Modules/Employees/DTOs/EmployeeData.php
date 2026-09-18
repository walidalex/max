<?php
declare(strict_types=1);
namespace App\Modules\Employees\DTOs;
final readonly class EmployeeData{public function __construct(public string$name,public ?string$phone,public ?string$email,public bool$isActive){}}
