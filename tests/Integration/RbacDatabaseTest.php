<?php

declare(strict_types=1);

use App\Modules\AccessControl\Repositories\PermissionRepository;
use App\Modules\AccessControl\Repositories\RoleRepository;

$expected = [
    'client_progress_statements.approve', 'client_progress_statements.cancel', 'client_progress_statements.create', 'client_progress_statements.edit', 'client_progress_statements.view',
    'project_costs.approve', 'project_costs.cancel', 'project_costs.create', 'project_costs.edit', 'project_costs.view',
    'subcontract_payments.cancel', 'subcontract_payments.create', 'subcontract_payments.edit', 'subcontract_payments.post', 'subcontract_payments.view',
    'subcontract_certificates.approve', 'subcontract_certificates.cancel', 'subcontract_certificates.create', 'subcontract_certificates.edit', 'subcontract_certificates.view',
    'subcontract_boq.approve', 'subcontract_boq.manage', 'subcontract_boq.view', 'subcontracts.change_status', 'subcontracts.create', 'subcontracts.edit', 'subcontracts.view',
    'client_contracts.change_status', 'client_contracts.create', 'client_contracts.edit', 'client_contracts.view', 'clients.activate', 'clients.create', 'clients.edit', 'clients.view',
    'company_profile.edit', 'company_profile.view', 'contract_boq.approve', 'contract_boq.manage', 'contract_boq.view', 'contract_variations.approve', 'contract_variations.cancel',
    'contract_variations.create', 'contract_variations.edit', 'contract_variations.view', 'cost_structure.manage', 'cost_structure.view', 'permissions.view', 'projects.change_status',
    'projects.create', 'projects.edit', 'projects.view', 'roles.assign_permissions', 'roles.create', 'roles.edit', 'roles.view', 'users.activate', 'users.assign_roles',
    'users.create', 'users.edit', 'users.view', 'vendors.activate', 'vendors.create', 'vendors.edit', 'vendors.view',
];
/** @var PermissionRepository $permissionRepository */
$permissionRepository = $app->make(PermissionRepository::class);
$actual = array_column($permissionRepository->all(), 'code');
sort($actual);
sort($expected);
if ($actual !== $expected) throw new RuntimeException('Core permission codes do not match the approved list.');
/** @var RoleRepository $roleRepository */
$roleRepository = $app->make(RoleRepository::class);
$roles = array_values(array_filter($roleRepository->all(), static fn(array $role): bool => $role['code'] === 'super_admin'));
if (count($roles) !== 1 || !(bool) $roles[0]['is_system'] || (int) $roles[0]['permissions_count'] !== count($expected)) throw new RuntimeException('The protected super_admin role is not configured correctly.');
