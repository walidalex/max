<?php

declare(strict_types=1);

namespace App\Modules\Foundation\Repositories;

use App\Core\Database\Database;

final class FoundationRepository
{
    public function __construct(private readonly Database $database) {}

    /** @return array{label: string, database_version: string} */
    public function check(string $label): array
    {
        $result = $this->database->execute('SELECT ? AS label, VERSION() AS database_version', [$label]);
        $row = $result instanceof \mysqli_result ? $result->fetch_assoc() : null;
        return ['label' => (string) ($row['label'] ?? ''), 'database_version' => (string) ($row['database_version'] ?? '')];
    }
}
