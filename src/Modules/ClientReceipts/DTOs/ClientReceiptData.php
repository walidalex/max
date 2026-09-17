<?php

declare(strict_types=1);

namespace App\Modules\ClientReceipts\DTOs;

final readonly class ClientReceiptData
{
    public function __construct(
        public int $contractId,
        public string $receiptDate,
        public ?string $receiptNumber,
        public string $amount,
        public string $paymentMethod,
        public ?string $referenceNumber,
        public ?string $notes,
    ) {}
}
