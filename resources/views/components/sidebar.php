<?php
$currentPath = '/' . trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
$currentPath = $currentPath === '/' ? '/' : $currentPath;
$can = is_callable($canView ?? null) ? $canView : static fn (string $permission): bool => false;
$isActive = static function (array $patterns, array $contains = []) use ($currentPath): bool {
    foreach ($patterns as $pattern) {
        if ($currentPath === $pattern || ($pattern !== '/' && str_starts_with($currentPath, $pattern . '/'))) {
            return true;
        }
    }

    foreach ($contains as $fragment) {
        if (str_contains($currentPath, $fragment)) {
            return true;
        }
    }

    return false;
};
$groups = [
    ['id'=>'master-data','label'=>'البيانات الأساسية','icon'=>'ti-database','items'=>[
        ['label'=>'العملاء','href'=>'/clients','icon'=>'ti-address-book','permission'=>'clients.view','patterns'=>['/clients']],
        ['label'=>'الموردون ومقاولو الباطن','href'=>'/vendors','icon'=>'ti-truck-delivery','permission'=>'vendors.view','patterns'=>['/vendors']],
        ['label'=>'المشاريع','href'=>'/projects','icon'=>'ti-briefcase','permission'=>'projects.view','patterns'=>['/projects']],
    ]],
    ['id'=>'clients-contracts','label'=>'العملاء والعقود','icon'=>'ti-file-certificate','items'=>[
        ['label'=>'عقود العملاء','href'=>'/contracts','icon'=>'ti-contract','permission'=>'client_contracts.view','patterns'=>['/contracts']],
        ['label'=>'مستخلصات العملاء','href'=>'/client-progress-statements','icon'=>'ti-file-dollar','permission'=>'client_progress_statements.view','patterns'=>['/client-progress-statements'],'contains'=>['/progress-statements']],
        ['label'=>'سندات قبض العملاء','href'=>'/client-receipts','icon'=>'ti-receipt','permission'=>'client_receipts.view','patterns'=>['/client-receipts'],'contains'=>['/receipts']],
    ]],
    ['id'=>'suppliers-procurement','label'=>'الموردون والمشتريات','icon'=>'ti-shopping-cart','items'=>[
        ['label'=>'فواتير الموردين','href'=>'/supplier-invoices','icon'=>'ti-file-invoice','permission'=>'supplier_invoices.view','patterns'=>['/supplier-invoices']],
        ['label'=>'مدفوعات الموردين','href'=>'/supplier-payments','icon'=>'ti-cash-banknote','permission'=>'supplier_payments.view','patterns'=>['/supplier-payments']],
    ]],
    ['id'=>'costs','label'=>'التكاليف','icon'=>'ti-coins','items'=>[
        ['label'=>'التكاليف الفعلية للمشاريع','href'=>'/project-costs','icon'=>'ti-cash','permission'=>'project_costs.view','patterns'=>['/project-costs']],
    ]],
    ['id'=>'subcontractors','label'=>'مقاولو الباطن','icon'=>'ti-building-factory-2','items'=>[
        ['label'=>'عقود مقاولي الباطن','href'=>'/subcontracts','icon'=>'ti-file-invoice','permission'=>'subcontracts.view','patterns'=>['/subcontracts','/subcontract-certificates','/subcontract-payments']],
    ]],
    ['id'=>'settings','label'=>'الإعدادات','icon'=>'ti-settings','items'=>[
        ['label'=>'ملف الشركة','href'=>'/settings/company','icon'=>'ti-building','permission'=>'company_profile.view','patterns'=>['/settings/company']],
        ['label'=>'هيكل التكاليف','href'=>'/settings/cost-structure','icon'=>'ti-hierarchy-2','permission'=>'cost_structure.view','patterns'=>['/settings/cost-structure']],
    ]],
    ['id'=>'system-administration','label'=>'إدارة النظام','icon'=>'ti-shield-lock','items'=>[
        ['label'=>'المستخدمون','href'=>'/access/users','icon'=>'ti-users','permission'=>'users.view','patterns'=>['/access/users']],
        ['label'=>'الأدوار','href'=>'/access/roles','icon'=>'ti-user-shield','permission'=>'roles.view','patterns'=>['/access/roles']],
        ['label'=>'الصلاحيات','href'=>'/access/permissions','icon'=>'ti-key','permission'=>'permissions.view','patterns'=>['/access/permissions']],
    ]],
];
foreach ($groups as &$group) {
    $group['items'] = array_values(array_filter($group['items'], static fn (array $item): bool => $can($item['permission'])));
}
unset($group);
$groups = array_values(array_filter($groups, static fn (array $group): bool => $group['items'] !== []));
?>
<aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu" aria-controls="sidebar-menu" aria-expanded="false" aria-label="فتح القائمة"><span class="navbar-toggler-icon"></span></button>
        <h1 class="navbar-brand navbar-brand-autodark">
            <a href="/" class="app-brand text-decoration-none">
                <?php if (is_array($companyBrand ?? null) && !empty($companyBrand['logo_path'])): ?>
                    <img class="app-brand-logo" src="/settings/company/logo?v=<?= urlencode((string) ($companyBrand['updated_at'] ?? '')) ?>" alt="شعار <?= e($companyBrand['name'] ?? 'الشركة') ?>">
                <?php else: ?>
                    <i class="ti ti-building-community app-brand-icon" aria-hidden="true"></i>
                <?php endif; ?>
                <span class="app-brand-name"><?= e($companyBrand['name'] ?? 'نظام المقاولات والديكور') ?></span>
            </a>
        </h1>
        <div class="collapse navbar-collapse" id="sidebar-menu">
            <ul class="navbar-nav pt-lg-3">
                <li class="nav-item">
                    <a class="nav-link<?= $currentPath === '/' ? ' active' : '' ?>" href="/">
                        <span class="nav-link-icon"><i class="ti ti-home"></i></span><span class="nav-link-title">الصفحة الرئيسية</span>
                    </a>
                </li>
                <?php foreach ($groups as $group): $groupActive = array_any($group['items'], static fn (array $item): bool => $isActive($item['patterns'], $item['contains'] ?? [])); ?>
                    <li class="nav-item">
                        <a class="nav-link<?= $groupActive ? ' active' : '' ?>" href="#sidebar-<?= e($group['id']) ?>" data-bs-toggle="collapse" role="button" aria-expanded="<?= $groupActive ? 'true' : 'false' ?>" aria-controls="sidebar-<?= e($group['id']) ?>">
                            <span class="nav-link-icon"><i class="ti <?= e($group['icon']) ?>"></i></span>
                            <span class="nav-link-title"><?= e($group['label']) ?></span>
                            <span class="nav-link-toggle"></span>
                        </a>
                        <div class="collapse<?= $groupActive ? ' show' : '' ?>" id="sidebar-<?= e($group['id']) ?>">
                            <ul class="nav nav-pills flex-column">
                                <?php foreach ($group['items'] as $item): $active = $isActive($item['patterns'], $item['contains'] ?? []); ?>
                                    <li class="nav-item">
                                        <a class="nav-link<?= $active ? ' active' : '' ?>" href="<?= e($item['href']) ?>">
                                            <span class="nav-link-icon"><i class="ti <?= e($item['icon']) ?>"></i></span><span class="nav-link-title"><?= e($item['label']) ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</aside>
