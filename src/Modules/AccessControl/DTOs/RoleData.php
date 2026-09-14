<?php
declare(strict_types=1);
namespace App\Modules\AccessControl\DTOs;
final readonly class RoleData
{
    public function __construct(public string $name, public string $code, public ?string $description, public bool $isActive) {}
}
