<?php

declare(strict_types=1);

namespace App\Modules\Foundation\Services;

use App\Modules\Foundation\DTOs\FoundationCheckData;
use App\Modules\Foundation\Repositories\FoundationRepository;

final class FoundationService
{
    public function __construct(private readonly FoundationRepository $repository) {}
    /** @return array{label: string, database_version: string} */
    public function check(FoundationCheckData $data): array { return $this->repository->check($data->label); }
}
