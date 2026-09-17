<?php
declare(strict_types=1);
namespace App\Modules\PurchaseOrders\DTOs;
final readonly class PurchaseOrderData
{
    /** @param list<array{description:string,cost_code_id:int,quantity:string,unit_price:string,sort_order:int}> $lines */
    public function __construct(public int $vendorId,public int $projectId,public string $poDate,public ?string $expectedDate,public ?string $reference,public ?string $notes,public array $lines) {}
}
