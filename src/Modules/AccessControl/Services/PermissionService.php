<?php
declare(strict_types=1);
namespace App\Modules\AccessControl\Services;
use App\Modules\AccessControl\Repositories\PermissionRepository;
final class PermissionService
{
    public function __construct(private readonly PermissionRepository $permissions) {}
    /** @return list<array<string,mixed>> */ public function all():array{return $this->permissions->all();}
}
