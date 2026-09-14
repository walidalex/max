<?php

declare(strict_types=1);

namespace App\Modules\Vendors\DTOs;

final readonly class VendorContactData
{
    public function __construct(
        public string $name,
        public ?string $jobTitle,
        public ?string $phone,
        public ?string $mobile,
        public ?string $email,
        public bool $isPrimary,
        public ?string $notes,
    ) {}
}
