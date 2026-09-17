<?php

declare(strict_types=1);

namespace App\Modules\ClientReceipts\DTOs;

final readonly class ClientReceiptTableQuery
{
    public function __construct(
        public int $draw,
        public int $start,
        public int $length,
        public string $search,
        public string $sortColumn,
        public string $sortDirection,
        public ?int $contractId,
        public ?int $projectId,
        public ?int $clientId,
        public ?string $status,
        public ?string $paymentMethod,
        public ?string $dateFrom,
        public ?string $dateTo,
    ) {}
}
