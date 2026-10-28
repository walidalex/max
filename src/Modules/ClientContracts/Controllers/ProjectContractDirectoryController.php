<?php

declare(strict_types=1);

namespace App\Modules\ClientContracts\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\View\View;
use App\Modules\AccessControl\Services\AuthorizationService;
use App\Modules\ClientContracts\Services\ClientContractService;
use App\Modules\Projects\Services\ProjectService;

final class ProjectContractDirectoryController
{
    public function __construct(
        private readonly View $view,
        private readonly AuthorizationService $authorization,
        private readonly ProjectService $projects,
        private readonly ClientContractService $contracts,
    ) {}

    public function index(Request $request): Response
    {
        $canViewProjects = $this->authorization->can('projects.view');
        $canViewContracts = $this->authorization->can('client_contracts.view');
        if (!$canViewProjects && !$canViewContracts) {
            return Response::html('ليس لديك صلاحية لعرض المشاريع أو عقود العملاء.', 403);
        }

        $projectReferences = $canViewProjects ? $this->projects->referenceData() : ['clients' => [], 'managers' => []];
        $contractReferences = $canViewContracts && !$canViewProjects ? $this->contracts->filterReferences() : ['projects' => [], 'clients' => []];

        return Response::html($this->view->render('modules/project-contracts/index', [
            'title' => 'المشاريع وعقود العملاء',
            'canViewProjects' => $canViewProjects,
            'canViewContracts' => $canViewContracts,
            'canCreateProject' => $this->authorization->can('projects.create'),
            'canEditProject' => $this->authorization->can('projects.edit'),
            'canChangeProjectStatus' => $this->authorization->can('projects.change_status'),
            'canCreateContract' => $this->authorization->can('client_contracts.create'),
            'canEditContract' => $this->authorization->can('client_contracts.edit'),
            'projectClients' => $projectReferences['clients'],
            'projectManagers' => $projectReferences['managers'],
            'contractProjects' => $contractReferences['projects'],
            'contractClients' => $contractReferences['clients'],
        ]));
    }
}
