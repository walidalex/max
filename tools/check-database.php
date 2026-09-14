<?php

declare(strict_types=1);

use App\Core\Database\Database;

$app = require dirname(__DIR__) . '/bootstrap/app.php';

try {
    /** @var Database $database */
    $database = $app->make(Database::class);
    $result = $database->execute(
        'SELECT DATABASE() AS database_name, VERSION() AS database_version, ? AS prepared_marker',
        ['prepared-ok'],
    );
    $row = $result instanceof mysqli_result ? $result->fetch_assoc() : null;

    echo json_encode([
        'connected' => true,
        'database' => $row['database_name'] ?? null,
        'server_version' => $row['database_version'] ?? null,
        'prepared_statement' => ($row['prepared_marker'] ?? null) === 'prepared-ok',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, json_encode([
        'connected' => false,
        'error' => $exception->getMessage(),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
    exit(1);
}
