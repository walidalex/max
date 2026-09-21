<?php

declare(strict_types=1);

namespace App\Modules\Vendors\Services;

use App\Core\Database\Database;
use App\Core\Exceptions\BusinessRuleException;
use App\Modules\Vendors\DTOs\VendorData;
use App\Modules\Vendors\DTOs\VendorTableQuery;
use App\Modules\Vendors\Repositories\VendorRepository;
use App\Shared\Numbering\NumberGeneratorService;

final class VendorService
{
    public function __construct(
        private readonly VendorRepository $vendors,
        private readonly NumberGeneratorService $numbers,
        private readonly Database $database,
    ) {}

    /** @return array<string, mixed> */
    public function find(int $id): array
    {
        return $this->vendors->find($id) ?? throw new BusinessRuleException('المورد أو مقاول الباطن غير موجود.');
    }

    public function save(VendorData $data, ?int $id = null): int
    {
        return $this->database->transaction(function () use ($data, $id): int {
            $existingIds = $id === null ? [] : $this->vendors->selectedWorkSectionIds($id);
            $this->validateWorkSections($data->workSectionIds, $existingIds);
            if ($id === null) {
                $id = $this->vendors->create($this->numbers->nextVendorCode(), $data);
                $this->vendors->syncWorkSections($id, $data->workSectionIds);
                return $id;
            }
            $this->find($id);
            $this->vendors->update($id, $data);
            $this->vendors->syncWorkSections($id, $data->workSectionIds);
            return $id;
        });
    }

    /** @return list<array<string, mixed>> */
    public function workSections(?int $vendorId = null): array
    {
        return $this->vendors->workSections($vendorId);
    }

    /** @param list<int> $selectedIds @param list<int> $existingIds */
    private function validateWorkSections(array $selectedIds, array $existingIds): void
    {
        $rows = $this->vendors->workSectionsByIds($selectedIds);
        if (count($rows) !== count($selectedIds)) {
            throw new BusinessRuleException('أحد مجالات العمل المختارة غير موجود.');
        }
        foreach ($rows as $row) {
            if ($row['is_active'] !== 1 && !in_array($row['id'], $existingIds, true)) {
                throw new BusinessRuleException('لا يمكن اختيار مجال عمل غير نشط.');
            }
        }
    }

    public function setActive(int $id, bool $active): void
    {
        $this->find($id);
        $this->vendors->setActive($id, $active);
    }

    /** @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>} */
    public function dataTable(VendorTableQuery $query): array
    {
        $result = $this->vendors->dataTable($query);
        return ['draw' => $query->draw, 'recordsTotal' => $result['recordsTotal'], 'recordsFiltered' => $result['recordsFiltered'], 'data' => $result['rows']];
    }
}
