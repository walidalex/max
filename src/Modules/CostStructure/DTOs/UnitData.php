<?php
declare(strict_types=1);
namespace App\Modules\CostStructure\DTOs;
final readonly class UnitData{public function __construct(public string $code,public string $nameAr,public ?string $symbolAr,public int $sortOrder){}}
