<?php

declare(strict_types=1);

namespace App\Modules\ClientProgressStatements\DTOs;

final readonly class StatementContractSelectionData
{
    public function __construct(public int $projectId, public int $contractId) {}
}
