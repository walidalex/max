<?php

declare(strict_types=1);

namespace App\Modules\Clients\Services;

use App\Core\Database\Database;
use App\Core\Exceptions\BusinessRuleException;
use App\Modules\Clients\DTOs\ClientData;
use App\Modules\Clients\DTOs\ClientTableQuery;
use App\Modules\Clients\Repositories\ClientRepository;
use App\Shared\Numbering\NumberGeneratorService;

final class ClientService
{
    public function __construct(
        private readonly ClientRepository $clients,
        private readonly NumberGeneratorService $numbers,
        private readonly Database $database,
    ) {}

    /** @return array<string, mixed> */
    public function find(int $id): array
    {
        return $this->clients->find($id) ?? throw new BusinessRuleException('العميل غير موجود.');
    }

    public function save(ClientData $data, ?int $id = null): int
    {
        return $this->database->transaction(function () use ($data, $id): int {
            if ($id === null) {
                return $this->clients->create($this->numbers->nextClientCode(), $data);
            }
            $this->find($id);
            $this->clients->update($id, $data);
            return $id;
        });
    }

    public function setActive(int $id, bool $active): void
    {
        $this->find($id);
        $this->clients->setActive($id, $active);
    }

    /** @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>} */
    public function dataTable(ClientTableQuery $query): array
    {
        $result = $this->clients->dataTable($query);
        return ['draw' => $query->draw, 'recordsTotal' => $result['recordsTotal'], 'recordsFiltered' => $result['recordsFiltered'], 'data' => $result['rows']];
    }
}
