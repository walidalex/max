<?php

declare(strict_types=1);

use App\Modules\SubcontractCertificates\Repositories\SubcontractCertificateRepository;

$repository = $app->make(SubcontractCertificateRepository::class);
foreach ([
    ['100.0000', '35.0000', '20.0000', '15.0000'],
    ['200.0000', '35.0000', '40.0000', '30.0000'],
    ['100.0000', '20.0000', '20.0000', '0.0000'],
    ['100.0000', '19.9999', '20.0000', null],
    ['3.0000', '33.3333', '0.0000', '1.0000'],
    ['100.0000', '100.0000', '35.0000', '65.0000'],
] as [$contractQuantity, $percentage, $previous, $expected]) {
    if ($repository->quantityFromCumulativePercentage($contractQuantity, $percentage, $previous) !== $expected) {
        throw new RuntimeException('Cumulative percentage conversion failed.');
    }
}
