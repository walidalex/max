<?php
declare(strict_types=1);
namespace App\Modules\CompanyProfile\DTOs;
use App\Core\Http\UploadedFile;
final readonly class CompanyProfileData
{
    public function __construct(
        public string $name,public ?string $legalName,public ?string $taxNumber,
        public ?string $commercialRegistration,public ?string $phone,public ?string $mobile,
        public ?string $email,public ?string $website,public ?string $address,public ?UploadedFile $logo,
    ) {}
}
