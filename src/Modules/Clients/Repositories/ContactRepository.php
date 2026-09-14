<?php

declare(strict_types=1);

namespace App\Modules\Clients\Repositories;

use App\Core\Database\Database;
use App\Modules\Clients\DTOs\ContactData;

final class ContactRepository
{
    public function __construct(private readonly Database $database) {}

    /** @return list<array<string, mixed>> */
    public function forClient(int $clientId): array
    {
        $result = $this->database->execute('SELECT * FROM client_contacts WHERE client_id = ? ORDER BY is_primary DESC, id ASC', [$clientId]);
        return $result instanceof \mysqli_result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /** @return array<string, mixed>|null */
    public function findForClient(int $id, int $clientId): ?array
    {
        $result = $this->database->execute('SELECT * FROM client_contacts WHERE id = ? AND client_id = ? LIMIT 1', [$id, $clientId]);
        $row = $result instanceof \mysqli_result ? $result->fetch_assoc() : null;
        return is_array($row) ? $row : null;
    }

    public function create(int $clientId, ContactData $data): int
    {
        $this->database->execute(
            'INSERT INTO client_contacts (client_id, name, job_title, phone, mobile, email, is_primary, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$clientId, $data->name, $data->jobTitle, $data->phone, $data->mobile, $data->email, $data->isPrimary, $data->notes],
        );
        return (int) $this->database->connection()->insert_id;
    }

    public function update(int $id, int $clientId, ContactData $data): void
    {
        $this->database->execute(
            'UPDATE client_contacts SET name = ?, job_title = ?, phone = ?, mobile = ?, email = ?, is_primary = ?, notes = ? WHERE id = ? AND client_id = ?',
            [$data->name, $data->jobTitle, $data->phone, $data->mobile, $data->email, $data->isPrimary, $data->notes, $id, $clientId],
        );
    }

    public function clearPrimary(int $clientId, ?int $exceptId = null): void
    {
        if ($exceptId === null) {
            $this->database->execute('UPDATE client_contacts SET is_primary = 0 WHERE client_id = ? AND is_primary = 1', [$clientId]);
            return;
        }
        $this->database->execute('UPDATE client_contacts SET is_primary = 0 WHERE client_id = ? AND id <> ? AND is_primary = 1', [$clientId, $exceptId]);
    }

    public function setPrimary(int $id, int $clientId): void
    {
        $this->database->execute('UPDATE client_contacts SET is_primary = 1 WHERE id = ? AND client_id = ?', [$id, $clientId]);
    }

    public function remove(int $id, int $clientId): void
    {
        $this->database->execute('DELETE FROM client_contacts WHERE id = ? AND client_id = ?', [$id, $clientId]);
    }
}
