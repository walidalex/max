<?php

declare(strict_types=1);

namespace App\Modules\ClientContracts\DTOs;

use App\Modules\Projects\DTOs\ProjectData;

final readonly class ProjectContractWizardData
{
    public function __construct(
        public ProjectData $project,
        public ClientContractData $contract,
    ) {}
}
