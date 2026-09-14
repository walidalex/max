<?php

declare(strict_types=1);

namespace App\Modules\Clients\DTOs;

final readonly class ClientData
{
    public function __construct(
        public string $clientType,
        public ?string $name,
        public ?string $companyName,
        public ?string $taxNumber,
        public ?string $commercialRegistration,
        public ?string $phone,
        public ?string $mobile,
        public ?string $email,
        public ?string $address,
        public ?string $notes,
    ) {}
}
