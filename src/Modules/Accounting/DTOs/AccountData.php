<?php
declare(strict_types=1);
namespace App\Modules\Accounting\DTOs;
final readonly class AccountData
{
    public function __construct(public string $code,public string $nameAr,public string $nameEn,public ?int $parentId,public string $type,public string $normalBalance,public bool $isPostable,public bool $isControl,public bool $isActive) {}
}
