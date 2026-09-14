<?php

declare(strict_types=1);

namespace App\Shared\Numbering;

use App\Core\Database\Database;

final class NumberSequenceRepository
{
    public function __construct(private readonly Database $database) {}

    public function next(string $key): int
    {
        $this->database->execute(
            'INSERT INTO number_sequences (sequence_key, current_value) VALUES (?, LAST_INSERT_ID(1)) '
            . 'ON DUPLICATE KEY UPDATE current_value = LAST_INSERT_ID(current_value + 1)',
            [$key],
        );

        $result = $this->database->execute('SELECT LAST_INSERT_ID() AS sequence_value');
        $row = $result instanceof \mysqli_result ? $result->fetch_assoc() : null;
        return (int) ($row['sequence_value'] ?? 0);
    }
}
