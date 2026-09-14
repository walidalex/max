<?php

declare(strict_types=1);

namespace App\Modules\Vendors\Repositories;

use App\Core\Database\Database;
use App\Modules\Vendors\DTOs\VendorData;
use App\Modules\Vendors\DTOs\VendorTableQuery;

final class VendorRepository
{
    public function __construct(private readonly Database $database) {}

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $result = $this->database->execute('SELECT * FROM vendors WHERE id = ? LIMIT 1', [$id]);
        $row = $result instanceof \mysqli_result ? $result->fetch_assoc() : null;
        return is_array($row) ? $row : null;
    }

    public function create(string $code, VendorData $data): int
    {
        $this->database->execute(
            'INSERT INTO vendors (vendor_code, vendor_type, name, tax_number, commercial_registration, phone, mobile, email, address, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$code, $data->vendorType, $data->name, $data->taxNumber, $data->commercialRegistration, $data->phone, $data->mobile, $data->email, $data->address, $data->notes],
        );
        return (int) $this->database->connection()->insert_id;
    }

    public function update(int $id, VendorData $data): void
    {
        $this->database->execute(
            'UPDATE vendors SET vendor_type = ?, name = ?, tax_number = ?, commercial_registration = ?, phone = ?, mobile = ?, email = ?, address = ?, notes = ? WHERE id = ?',
            [$data->vendorType, $data->name, $data->taxNumber, $data->commercialRegistration, $data->phone, $data->mobile, $data->email, $data->address, $data->notes, $id],
        );
    }

    public function setActive(int $id, bool $active): void
    {
        $this->database->execute('UPDATE vendors SET is_active = ? WHERE id = ?', [$active, $id]);
    }

    /** @return array{recordsTotal: int, recordsFiltered: int, rows: list<array<string, mixed>>} */
    public function dataTable(VendorTableQuery $query): array
    {
        $from = ' FROM vendors c LEFT JOIN vendor_contacts pc ON pc.vendor_id = c.id AND pc.is_primary = 1';
        [$where, $params] = $this->filters($query);
        $totalResult = $this->database->execute('SELECT COUNT(*) AS total FROM vendors');
        $totalRow = $totalResult instanceof \mysqli_result ? $totalResult->fetch_assoc() : [];
        $filteredResult = $this->database->execute('SELECT COUNT(*) AS total' . $from . $where, $params);
        $filteredRow = $filteredResult instanceof \mysqli_result ? $filteredResult->fetch_assoc() : [];

        $sortColumns = [
            'vendor_code' => 'c.vendor_code',
            'display_name' => 'c.name',
            'vendor_type' => 'c.vendor_type',
            'contact_number' => "COALESCE(c.mobile, c.phone, '')",
            'primary_contact' => "COALESCE(pc.name, '')",
            'is_active' => 'c.is_active',
        ];
        $orderBy = $sortColumns[$query->sortColumn] ?? 'c.vendor_code';
        $sql = "SELECT c.id, c.vendor_code, c.vendor_type, c.name, c.phone, c.mobile, c.is_active, pc.name AS primary_contact{$from}{$where} ORDER BY {$orderBy} {$query->sortDirection} LIMIT ? OFFSET ?";
        $result = $this->database->execute($sql, [...$params, $query->length, $query->start]);

        return [
            'recordsTotal' => (int) ($totalRow['total'] ?? 0),
            'recordsFiltered' => (int) ($filteredRow['total'] ?? 0),
            'rows' => $result instanceof \mysqli_result ? $result->fetch_all(MYSQLI_ASSOC) : [],
        ];
    }

    /** @return array{string, list<mixed>} */
    private function filters(VendorTableQuery $query): array
    {
        $conditions = [];
        $params = [];
        if ($query->search !== '') {
            $search = '%' . $query->search . '%';
            $conditions[] = '(c.vendor_code LIKE ? OR c.name LIKE ? OR c.phone LIKE ? OR c.mobile LIKE ? OR pc.name LIKE ?)';
            array_push($params, $search, $search, $search, $search, $search);
        }
        if ($query->isActive !== null) {
            $conditions[] = 'c.is_active = ?';
            $params[] = $query->isActive;
        }
        if ($query->vendorType !== null) {
            $conditions[] = 'c.vendor_type = ?';
            $params[] = $query->vendorType;
        }
        return [$conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions), $params];
    }
}
