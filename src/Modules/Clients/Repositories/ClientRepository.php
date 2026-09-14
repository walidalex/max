<?php

declare(strict_types=1);

namespace App\Modules\Clients\Repositories;

use App\Core\Database\Database;
use App\Modules\Clients\DTOs\ClientData;
use App\Modules\Clients\DTOs\ClientTableQuery;

final class ClientRepository
{
    public function __construct(private readonly Database $database) {}

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $result = $this->database->execute('SELECT * FROM clients WHERE id = ? LIMIT 1', [$id]);
        $row = $result instanceof \mysqli_result ? $result->fetch_assoc() : null;
        return is_array($row) ? $row : null;
    }

    public function create(string $code, ClientData $data): int
    {
        $this->database->execute(
            'INSERT INTO clients (client_code, client_type, name, company_name, tax_number, commercial_registration, phone, mobile, email, address, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$code, $data->clientType, $data->name, $data->companyName, $data->taxNumber, $data->commercialRegistration, $data->phone, $data->mobile, $data->email, $data->address, $data->notes],
        );
        return (int) $this->database->connection()->insert_id;
    }

    public function update(int $id, ClientData $data): void
    {
        $this->database->execute(
            'UPDATE clients SET client_type = ?, name = ?, company_name = ?, tax_number = ?, commercial_registration = ?, phone = ?, mobile = ?, email = ?, address = ?, notes = ? WHERE id = ?',
            [$data->clientType, $data->name, $data->companyName, $data->taxNumber, $data->commercialRegistration, $data->phone, $data->mobile, $data->email, $data->address, $data->notes, $id],
        );
    }

    public function setActive(int $id, bool $active): void
    {
        $this->database->execute('UPDATE clients SET is_active = ? WHERE id = ?', [$active, $id]);
    }

    /** @return array{recordsTotal: int, recordsFiltered: int, rows: list<array<string, mixed>>} */
    public function dataTable(ClientTableQuery $query): array
    {
        $from = ' FROM clients c LEFT JOIN client_contacts pc ON pc.client_id = c.id AND pc.is_primary = 1';
        [$where, $params] = $this->filters($query);
        $totalResult = $this->database->execute('SELECT COUNT(*) AS total FROM clients');
        $totalRow = $totalResult instanceof \mysqli_result ? $totalResult->fetch_assoc() : [];
        $filteredResult = $this->database->execute('SELECT COUNT(*) AS total' . $from . $where, $params);
        $filteredRow = $filteredResult instanceof \mysqli_result ? $filteredResult->fetch_assoc() : [];

        $sortColumns = [
            'client_code' => 'c.client_code',
            'display_name' => "COALESCE(c.company_name, c.name, '')",
            'client_type' => 'c.client_type',
            'contact_number' => "COALESCE(c.mobile, c.phone, '')",
            'primary_contact' => "COALESCE(pc.name, '')",
            'is_active' => 'c.is_active',
        ];
        $orderBy = $sortColumns[$query->sortColumn] ?? 'c.client_code';
        $sql = "SELECT c.id, c.client_code, c.client_type, c.name, c.company_name, c.phone, c.mobile, c.is_active, pc.name AS primary_contact{$from}{$where} ORDER BY {$orderBy} {$query->sortDirection} LIMIT ? OFFSET ?";
        $result = $this->database->execute($sql, [...$params, $query->length, $query->start]);

        return [
            'recordsTotal' => (int) ($totalRow['total'] ?? 0),
            'recordsFiltered' => (int) ($filteredRow['total'] ?? 0),
            'rows' => $result instanceof \mysqli_result ? $result->fetch_all(MYSQLI_ASSOC) : [],
        ];
    }

    /** @return array{string, list<mixed>} */
    private function filters(ClientTableQuery $query): array
    {
        $conditions = [];
        $params = [];
        if ($query->search !== '') {
            $search = '%' . $query->search . '%';
            $conditions[] = '(c.client_code LIKE ? OR c.name LIKE ? OR c.company_name LIKE ? OR c.phone LIKE ? OR c.mobile LIKE ? OR pc.name LIKE ?)';
            array_push($params, $search, $search, $search, $search, $search, $search);
        }
        if ($query->isActive !== null) {
            $conditions[] = 'c.is_active = ?';
            $params[] = $query->isActive;
        }
        if ($query->clientType !== null) {
            $conditions[] = 'c.client_type = ?';
            $params[] = $query->clientType;
        }
        return [$conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions), $params];
    }
}
