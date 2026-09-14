<?php

declare(strict_types=1);

namespace App\Modules\Vendors\Repositories;

use App\Core\Database\Database;
use App\Modules\Vendors\DTOs\VendorContactData;

final class ContactRepository
{
    public function __construct(private readonly Database $database) {}

    /** @return list<array<string, mixed>> */
    public function forVendor(int $vendorId): array
    {
        $result = $this->database->execute('SELECT * FROM vendor_contacts WHERE vendor_id = ? ORDER BY is_primary DESC, id ASC', [$vendorId]);
        return $result instanceof \mysqli_result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /** @return array<string, mixed>|null */
    public function findForVendor(int $id, int $vendorId): ?array
    {
        $result = $this->database->execute('SELECT * FROM vendor_contacts WHERE id = ? AND vendor_id = ? LIMIT 1', [$id, $vendorId]);
        $row = $result instanceof \mysqli_result ? $result->fetch_assoc() : null;
        return is_array($row) ? $row : null;
    }

    public function create(int $vendorId, VendorContactData $data): int
    {
        $this->database->execute(
            'INSERT INTO vendor_contacts (vendor_id, name, job_title, phone, mobile, email, is_primary, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$vendorId, $data->name, $data->jobTitle, $data->phone, $data->mobile, $data->email, $data->isPrimary, $data->notes],
        );
        return (int) $this->database->connection()->insert_id;
    }

    public function update(int $id, int $vendorId, VendorContactData $data): void
    {
        $this->database->execute(
            'UPDATE vendor_contacts SET name = ?, job_title = ?, phone = ?, mobile = ?, email = ?, is_primary = ?, notes = ? WHERE id = ? AND vendor_id = ?',
            [$data->name, $data->jobTitle, $data->phone, $data->mobile, $data->email, $data->isPrimary, $data->notes, $id, $vendorId],
        );
    }

    public function clearPrimary(int $vendorId, ?int $exceptId = null): void
    {
        if ($exceptId === null) {
            $this->database->execute('UPDATE vendor_contacts SET is_primary = 0 WHERE vendor_id = ? AND is_primary = 1', [$vendorId]);
            return;
        }
        $this->database->execute('UPDATE vendor_contacts SET is_primary = 0 WHERE vendor_id = ? AND id <> ? AND is_primary = 1', [$vendorId, $exceptId]);
    }

    public function setPrimary(int $id, int $vendorId): void
    {
        $this->database->execute('UPDATE vendor_contacts SET is_primary = 1 WHERE id = ? AND vendor_id = ?', [$id, $vendorId]);
    }

    public function remove(int $id, int $vendorId): void
    {
        $this->database->execute('DELETE FROM vendor_contacts WHERE id = ? AND vendor_id = ?', [$id, $vendorId]);
    }
}
