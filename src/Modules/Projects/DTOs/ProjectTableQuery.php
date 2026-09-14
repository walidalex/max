<?php
declare(strict_types=1);
namespace App\Modules\Projects\DTOs;
final readonly class ProjectTableQuery
{
 public function __construct(public int $draw,public int $start,public int $length,public string $search,public string $sortColumn,public string $sortDirection,public ?int $clientId,public ?int $projectManagerId,public ?string $projectType,public ?string $status){}
}
