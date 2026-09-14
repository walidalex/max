<?php
declare(strict_types=1);
namespace App\Modules\ProjectCosts\DTOs;
final readonly class ProjectCostData{public function __construct(public int $projectId,public string $costDate,public int $costCodeId,public ?int $vendorId,public string $description,public ?string $referenceNumber,public string $amount,public ?string $notes){}}
