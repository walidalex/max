<?php
declare(strict_types=1);
namespace App\Modules\AccessControl\DTOs;
final readonly class UserData
{
    /** @param list<int> $roleIds */
    public function __construct(public string $username, public string $name, public ?string $email, public ?string $password, public array $roleIds) {}
}
