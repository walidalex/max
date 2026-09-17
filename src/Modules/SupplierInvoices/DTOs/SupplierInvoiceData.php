<?php
declare(strict_types=1);
namespace App\Modules\SupplierInvoices\DTOs;
final readonly class SupplierInvoiceData
{
    /** @param list<array{description:string,cost_code_id:int,amount:string,sort_order:int}> $lines */
    public function __construct(public int $vendorId,public int $projectId,public ?int $purchaseOrderId,public string $invoiceNumber,public string $invoiceDate,public ?string $dueDate,public ?string $reference,public ?string $notes,public array $lines) {}
}
