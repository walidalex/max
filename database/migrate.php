<?php

declare(strict_types=1);

use App\Core\Database\Database;

$app = require dirname(__DIR__) . '/bootstrap/app.php';
/** @var Database $database */
$database = $app->make(Database::class);
$connection = $database->connection();
$connection->query(file_get_contents(__DIR__ . '/migrations/001_create_schema_migrations.sql'));

foreach (glob(__DIR__ . '/migrations/*.sql') ?: [] as $file) {
    $name = basename($file);
    $check = $connection->prepare('SELECT 1 FROM schema_migrations WHERE migration = ?');
    $check->bind_param('s', $name);
    $check->execute();
    if ($check->get_result()->fetch_row()) { $check->close(); continue; }
    $check->close();
    $connection->begin_transaction();
    try {
        $connection->query((string) file_get_contents($file));
        $record = $connection->prepare('INSERT INTO schema_migrations (migration) VALUES (?)');
        $record->bind_param('s', $name);
        $record->execute();
        $record->close();
        $connection->commit();
        echo "Migrated: {$name}" . PHP_EOL;
    } catch (Throwable $exception) {
        $connection->rollback();
        throw $exception;
    }
}
