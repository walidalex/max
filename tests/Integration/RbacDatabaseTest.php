<?php
declare(strict_types=1);

use App\Modules\AccessControl\Repositories\PermissionRepository;
use App\Modules\AccessControl\Repositories\RoleRepository;

$expected=['clients.activate','clients.create','clients.edit','clients.view','company_profile.edit','company_profile.view','permissions.view','projects.change_status','projects.create','projects.edit','projects.view','roles.assign_permissions','roles.create','roles.edit','roles.view','users.activate','users.assign_roles','users.create','users.edit','users.view','vendors.activate','vendors.create','vendors.edit','vendors.view'];
/** @var PermissionRepository $permissionRepository */
$permissionRepository=$app->make(PermissionRepository::class);
$actual=array_column($permissionRepository->all(),'code');sort($actual);sort($expected);
if($actual!==$expected){throw new RuntimeException('Core permission codes do not match the approved list.');}
/** @var RoleRepository $roleRepository */
$roleRepository=$app->make(RoleRepository::class);
$roles=array_values(array_filter($roleRepository->all(),static fn(array $role):bool=>$role['code']==='super_admin'));
if(count($roles)!==1||!(bool)$roles[0]['is_system']||(int)$roles[0]['permissions_count']!==count($expected)){throw new RuntimeException('The protected super_admin role is not configured correctly.');}
