<?php
declare(strict_types=1);
namespace App\Modules\ClientContracts\DTOs;
final readonly class ClientContractTableQuery { public function __construct(public int $draw,public int $start,public int $length,public string $search,public string $sortColumn,public string $sortDirection,public ?int $projectId,public ?int $clientId,public ?string $pricingMethod,public ?string $status,public ?int $year){} }
