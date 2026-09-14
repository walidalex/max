<?php

declare(strict_types=1);

namespace App\Modules\Vendors\DTOs;

final readonly class VendorTableQuery
{
    public function __construct(
        public int $draw,
        public int $start,
        public int $length,
        public string $search,
        public string $sortColumn,
        public string $sortDirection,
        public ?bool $isActive,
        public ?string $vendorType,
    ) {}
}
