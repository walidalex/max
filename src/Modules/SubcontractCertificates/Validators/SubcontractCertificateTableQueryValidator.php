<?php

declare(strict_types=1);

namespace App\Modules\SubcontractCertificates\Validators;

use App\Modules\SubcontractCertificates\DTOs\SubcontractCertificateTableQuery;

final class SubcontractCertificateTableQueryValidator
{
    public function validate(array $input, int $subcontractId): SubcontractCertificateTableQuery
    {
        $columns = ['certificate_code', 'certificate_number', 'certificate_date', 'period_from', 'previous_earned_value', 'current_earned_value', 'cumulative_earned_value', 'status'];
        $index = max(0, (int) ($input['order'][0]['column'] ?? 0));
        $status = (string) ($input['status'] ?? '');
        return new SubcontractCertificateTableQuery(
            max(0, (int) ($input['draw'] ?? 0)),
            max(0, (int) ($input['start'] ?? 0)),
            min(100, max(10, (int) ($input['length'] ?? 10))),
            mb_substr(trim((string) ($input['search']['value'] ?? '')), 0, 100),
            $columns[$index] ?? 'certificate_code',
            strtolower((string) ($input['order'][0]['dir'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC',
            max(1, $subcontractId),
            in_array($status, ['draft', 'approved', 'cancelled'], true) ? $status : null,
        );
    }
}
