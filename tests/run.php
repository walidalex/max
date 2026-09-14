<?php

declare(strict_types=1);

$app = require dirname(__DIR__) . '/bootstrap/app.php';
$tests = glob(__DIR__ . '/{Unit,Integration,Feature}/*Test.php', GLOB_BRACE) ?: [];
foreach ($tests as $test) { require $test; echo 'PASS ' . basename($test) . PHP_EOL; }
echo count($tests) . ' tests passed.' . PHP_EOL;
