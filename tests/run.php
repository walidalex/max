<?php

declare(strict_types=1);

use App\Core\Database\Database;

$app = require dirname(__DIR__) . '/bootstrap/app.php';
$environment = (string) env('APP_ENV', '');
$database = (string) env('DB_DATABASE', '');
if ($environment !== 'testing' || !str_ends_with(strtolower($database), '_test')) {
    fwrite(STDERR, "Refusing to run tests: APP_ENV must be testing and DB_DATABASE must end with _test. Current APP_ENV={$environment}, DB_DATABASE={$database}." . PHP_EOL);
    exit(2);
}
echo "Test environment: {$environment}; database: {$database}" . PHP_EOL;
$tests = glob(__DIR__ . '/{Unit,Integration,Feature}/*Test.php', GLOB_BRACE) ?: [];

/** @var Database $testDatabase */
$testDatabase = $app->make(Database::class);
$activeUser = $testDatabase->execute('SELECT id FROM users WHERE is_active = 1 LIMIT 1')->fetch_assoc();
$fixtureUserId = null;
if ($activeUser === null) {
    $testDatabase->execute(
        'INSERT INTO users (username, name, password_hash) VALUES (?, ?, ?)',
        ['_test_runner', 'Test Runner', password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT)],
    );
    $fixtureUserId = $testDatabase->connection()->insert_id;
}

try {
    foreach ($tests as $test) {
        require $test;
        echo 'PASS ' . basename($test) . PHP_EOL;
    }
    echo count($tests) . ' tests passed.' . PHP_EOL;
} finally {
    if ($fixtureUserId !== null) {
        $testDatabase->execute('DELETE FROM users WHERE id = ?', [$fixtureUserId]);
    }
}
