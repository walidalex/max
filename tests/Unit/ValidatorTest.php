<?php

declare(strict_types=1);

use App\Core\Validation\Validator;

$validator = new Validator();
if (!$validator->validate(['name' => 'Foundation'], ['name' => 'required|string|max:20'])) {
    throw new RuntimeException('Valid input was rejected.');
}
if ($validator->validate(['name' => ''], ['name' => 'required'])) {
    throw new RuntimeException('Invalid input was accepted.');
}
