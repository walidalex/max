<?php
declare(strict_types=1);
namespace App\Modules\CostStructure\DTOs;
final readonly class CostCodeData{public function __construct(public int $workSectionId,public string $costCode,public string $name,public ?int $defaultUnitId,public ?string $description,public int $sortOrder){}}
