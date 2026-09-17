<?php

declare(strict_types=1);

namespace App\Modules\ClientReceipts\DTOs;

final readonly class ClientReceiptAllocationData
{
    /** @param list<array{statement_id:int,allocated_amount:string,notes:?string}> $allocations */
    public function __construct(public array $allocations) {}
}
