<?php

declare(strict_types=1);

namespace App\Modules\SubcontractPayments\DTOs;

final readonly class SubcontractPaymentData
{
    public function __construct(public int $subcontractId, public string $paymentDate, public string $amount, public string $paymentMethod, public ?string $referenceNumber, public ?string $notes) {}
}
