<?php
declare(strict_types=1);
namespace App\Modules\Projects\DTOs;
final readonly class ProjectData
{
 public function __construct(public string $name,public int $clientId,public ?int $primaryContactId,public ?int $projectManagerId,public string $projectType,public string $status,public ?string $startDate,public ?string $expectedEndDate,public ?string $actualEndDate,public ?string $siteAddress,public ?string $city,public ?string $area,public ?string $description,public ?string $notes){}
}
