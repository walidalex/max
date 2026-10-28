<?php

declare(strict_types=1);

namespace App\Modules\ClientContracts\Services;

use App\Core\Database\Database;
use App\Core\Exceptions\BusinessRuleException;
use App\Modules\AccessControl\Services\AuthorizationService;
use App\Modules\ClientContracts\DTOs\ClientContractData;
use App\Modules\ClientContracts\DTOs\ProjectContractWizardData;
use App\Modules\Projects\Services\ProjectService;

final class ProjectContractWizardService
{
    public function __construct(
        private readonly Database $db,
        private readonly ProjectService $projects,
        private readonly ClientContractService $contracts,
        private readonly AuthorizationService $authorization,
    ) {}

    /** @return array{projectId: int, contractId: int} */
    public function save(ProjectContractWizardData $data): array
    {
        if (!$this->authorization->can('projects.create')) {
            throw new BusinessRuleException('ليس لديك صلاحية إضافة مشروع.');
        }
        if (!$this->authorization->can('client_contracts.create')) {
            throw new BusinessRuleException('ليس لديك صلاحية إضافة عقد عميل.');
        }
        return $this->db->transaction(function () use ($data): array {
            $projectId = $this->projects->save($data->project);
            $c = $data->contract;
            $contractId = $this->contracts->save(new ClientContractData(
                $projectId, $data->project->clientId, $c->clientContactId,
                $c->pricingMethod, $c->contractDate, $c->startDate, $c->expectedEndDate,
                $c->contractValue, $c->markupPercentage, null, $c->title,
                $c->description, $c->terms, $c->notes,
            ));
            return compact('projectId', 'contractId');
        });
    }
}
