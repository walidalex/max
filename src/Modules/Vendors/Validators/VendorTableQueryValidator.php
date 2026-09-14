<?php

declare(strict_types=1);

namespace App\Modules\Vendors\Validators;

use App\Modules\Vendors\DTOs\VendorTableQuery;

final class VendorTableQueryValidator
{
    /** @param array<string, mixed> $input */
    public function validate(array $input): VendorTableQuery
    {
        $columns = ['vendor_code', 'display_name', 'vendor_type', 'contact_number', 'primary_contact', 'is_active'];
        $columnIndex = max(0, (int) ($input['order'][0]['column'] ?? 0));
        $sortColumn = $columns[$columnIndex] ?? 'vendor_code';
        $direction = strtolower((string) ($input['order'][0]['dir'] ?? 'asc')) === 'desc' ? 'DESC' : 'ASC';
        $status = (string) ($input['status'] ?? '');
        $type = (string) ($input['vendor_type'] ?? '');

        return new VendorTableQuery(
            max(0, (int) ($input['draw'] ?? 0)),
            max(0, (int) ($input['start'] ?? 0)),
            min(100, max(10, (int) ($input['length'] ?? 10))),
            mb_substr(trim((string) ($input['search']['value'] ?? '')), 0, 100),
            $sortColumn,
            $direction,
            $status === 'active' ? true : ($status === 'inactive' ? false : null),
            in_array($type, ['supplier', 'subcontractor', 'both'], true) ? $type : null,
        );
    }
}
