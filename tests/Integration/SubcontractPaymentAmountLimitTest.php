<?php

declare(strict_types=1);

use App\Modules\SubcontractPayments\Repositories\SubcontractPaymentRepository;

$repository = $app->make(SubcontractPaymentRepository::class);
foreach ([
    ['100.01', '100.00', true],
    ['100.00', '100.00', false],
    ['9.00', '100.00', false],
    ['1000.00', '900.00', true],
    ['0.01', '0.00', true],
    ['9999999999999999.99', '9999999999999999.98', true],
] as [$amount, $available, $expected]) {
    if ($repository->amountExceeds($amount, $available) !== $expected) {
        throw new RuntimeException('Subcontract payment amount limit comparison failed.');
    }
}
