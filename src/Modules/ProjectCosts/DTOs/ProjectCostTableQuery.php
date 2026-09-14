<?php
declare(strict_types=1);
namespace App\Modules\ProjectCosts\DTOs;
final readonly class ProjectCostTableQuery{public function __construct(public int $draw,public int $start,public int $length,public string $search,public string $sortColumn,public string $sortDirection,public ?int $projectId,public ?int $workSectionId,public ?int $costCodeId,public ?int $vendorId,public ?string $status,public ?string $dateFrom,public ?string $dateTo,public ?string $sourceType){}}
