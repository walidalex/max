<?php

declare(strict_types=1);

namespace App\Modules\SubcontractPayments\DTOs;

final readonly class SubcontractPaymentTableQuery
{
    public function __construct(public int $draw, public int $start, public int $length, public string $search, public string $sortColumn, public string $sortDirection, public int $subcontractId, public ?string $status, public ?string $method, public ?string $dateFrom, public ?string $dateTo) {}
}
