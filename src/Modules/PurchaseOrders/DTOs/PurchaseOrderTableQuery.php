<?php
declare(strict_types=1);
namespace App\Modules\PurchaseOrders\DTOs;
final readonly class PurchaseOrderTableQuery
{
    public function __construct(public int $draw,public int $start,public int $length,public string $search,public string $sortColumn,public string $sortDirection,public ?int $vendorId,public ?int $projectId,public ?string $status,public ?string $dateFrom,public ?string $dateTo) {}
}
