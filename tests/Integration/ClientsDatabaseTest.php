<?php

declare(strict_types=1);

use App\Core\Database\Database;
use App\Modules\Clients\Services\ClientService;
use App\Modules\Clients\Validators\ClientTableQueryValidator;

/** @var Database $database */
$database = $app->make(Database::class);
$tables = [];
$result = $database->execute("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('clients', 'client_contacts', 'number_sequences')");
if ($result instanceof mysqli_result) {
    $tables = array_column($result->fetch_all(MYSQLI_ASSOC), 'TABLE_NAME');
}
sort($tables);
if ($tables !== ['client_contacts', 'clients', 'number_sequences']) {
    throw new RuntimeException('Clients database tables are missing.');
}
$sequence = $database->execute('SELECT current_value FROM number_sequences WHERE sequence_key = ? LIMIT 1', ['clients']);
if (!($sequence instanceof mysqli_result) || $sequence->num_rows !== 1) {
    throw new RuntimeException('Client number sequence is missing.');
}
/** @var ClientService $clientService */
$clientService = $app->make(ClientService::class);
$tableResult = $clientService->dataTable((new ClientTableQueryValidator())->validate([
    'draw' => '1',
    'start' => '0',
    'length' => '10',
    'search' => ['value' => ''],
    'order' => [['column' => '0', 'dir' => 'asc']],
]));
if ($tableResult['draw'] !== 1 || !is_array($tableResult['data'])) {
    throw new RuntimeException('Server-side clients table query failed.');
}
