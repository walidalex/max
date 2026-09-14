<?php

declare(strict_types=1);

use App\Core\Http\Request;

$app = require dirname(__DIR__) . '/bootstrap/app.php';
$app->handle(Request::capture())->send();
