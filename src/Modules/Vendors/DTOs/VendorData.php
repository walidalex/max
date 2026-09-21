<?php

declare(strict_types=1);

namespace App\Modules\Vendors\DTOs;

final readonly class VendorData
{
    public function __construct(
        public string $vendorType,
        public string $name,
        public ?string $taxNumber,
        public ?string $commercialRegistration,
        public ?string $phone,
        public ?string $mobile,
        public ?string $email,
        public ?string $address,
        public ?string $notes,
        /** @var list<int> */
        public array $workSectionIds,
    ) {}
}
