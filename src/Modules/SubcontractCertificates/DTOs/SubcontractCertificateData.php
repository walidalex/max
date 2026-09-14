<?php

declare(strict_types=1);

namespace App\Modules\SubcontractCertificates\DTOs;

final readonly class SubcontractCertificateData
{
    /** @param array<int, string> $quantities */
    public function __construct(
        public int $subcontractId,
        public ?string $certificateNumber,
        public ?string $periodFrom,
        public ?string $periodTo,
        public string $certificateDate,
        public ?string $currentProgressPercentage,
        public array $quantities,
        public ?string $notes,
    ) {}
}
