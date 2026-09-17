<?php

declare(strict_types=1);

$renderSidebar = static function (string $uri): string {
    $_SERVER['REQUEST_URI'] = $uri;
    $companyBrand = null;
    $canView = static fn (string $permission): bool => in_array($permission, [
        'projects.view',
        'project_costs.view',
        'client_contracts.view',
    ], true);

    ob_start();
    require dirname(__DIR__, 2) . '/resources/views/components/sidebar.php';
    return (string) ob_get_clean();
};

$variationSidebar = $renderSidebar('/contract-variations/15/edit');
if (!str_contains($variationSidebar, 'class="collapse show" id="sidebar-clients-contracts"')
    || preg_match('#class="nav-link active" href="/contracts"#', $variationSidebar) !== 1) {
    throw new RuntimeException('Contract variation route did not activate Client Contracts.');
}

$projectCostsSidebar = $renderSidebar('/projects/42/costs/create');
if (!str_contains($projectCostsSidebar, 'class="collapse show" id="sidebar-costs"')
    || preg_match('#class="nav-link active" href="/project-costs"#', $projectCostsSidebar) !== 1
    || str_contains($projectCostsSidebar, 'class="collapse show" id="sidebar-master-data"')
    || preg_match('#class="nav-link active" href="/projects"#', $projectCostsSidebar) === 1) {
    throw new RuntimeException('Project costs route did not activate Project Actual Costs exclusively.');
}
