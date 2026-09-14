<?php
declare(strict_types=1);
namespace App\Modules\CostStructure\DTOs;
final readonly class WorkSectionData{public function __construct(public string $sectionCode,public string $name,public ?string $description,public int $sortOrder){}}
