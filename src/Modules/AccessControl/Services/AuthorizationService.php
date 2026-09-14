<?php
declare(strict_types=1);
namespace App\Modules\AccessControl\Services;
use App\Core\Auth\Auth;
use App\Modules\AccessControl\Repositories\AuthorizationRepository;
final class AuthorizationService
{
    public function __construct(private readonly AuthorizationRepository $authorization,private readonly Auth $auth) {}
    public function can(string $permission): bool
    {
        $id=$this->auth->id();
        if ($id===null) { return false; }
        if ($this->authorization->userHasRole($id,'super_admin')) { return true; }
        return $this->authorization->userHasPermission($id,$permission);
    }
    public function hasRole(string $role): bool { $id=$this->auth->id(); return $id!==null && $this->authorization->userHasRole($id,$role); }
}
