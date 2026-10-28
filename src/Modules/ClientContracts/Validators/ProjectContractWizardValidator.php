<?php

declare(strict_types=1);

namespace App\Modules\ClientContracts\Validators;

use App\Modules\ClientContracts\DTOs\ProjectContractWizardData;
use App\Modules\Projects\Validators\ProjectValidator;

final class ProjectContractWizardValidator
{
    public function __construct(
        private readonly ProjectValidator $projects,
        private readonly ClientContractValidator $contracts,
    ) {}

    public function validate(array $input): ProjectContractWizardData
    {
        $projectInput = is_array($input['project'] ?? null) ? $input['project'] : [];
        $contractInput = is_array($input['contract'] ?? null) ? $input['contract'] : [];
        $project = $this->projects->validate([...$projectInput, 'status' => 'planning']);
        // A new project receives its real ID inside the saving transaction.
        $contract = $this->contracts->validate([
            ...$contractInput,
            'project_id' => 1,
            'client_id' => $project->clientId,
            'contract_number' => '',
        ]);
        return new ProjectContractWizardData($project, $contract);
    }
}
