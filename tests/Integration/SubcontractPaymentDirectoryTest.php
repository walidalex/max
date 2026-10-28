<?php

declare(strict_types=1);

use App\Modules\SubcontractPayments\Repositories\SubcontractPaymentRepository;
use App\Modules\SubcontractPayments\Validators\SubcontractPaymentTableQueryValidator;

$validator = new SubcontractPaymentTableQueryValidator();
$repository = $app->make(SubcontractPaymentRepository::class);
$global = $validator->validate([], null);
if ($global->subcontractId !== null) throw new RuntimeException('Global payment query unexpectedly scoped.');
$result = $repository->dataTable($global);
if ($result['recordsFiltered'] !== $result['recordsTotal']) throw new RuntimeException('Unfiltered payment totals differ.');
$scoped = $validator->validate(['subcontract_id' => '999', 'vendor_id'=>'3','project_id'=>'4'], 7);
if ($scoped->subcontractId !== 7 || $scoped->vendorId !== 3 || $scoped->projectId !== 4) throw new RuntimeException('Payment route scope or filters lost.');
$filtered = $repository->dataTable($validator->validate(['subcontract_id'=>'7','search'=>['value'=>'not-a-real-payment-identifier']], null));
if ($filtered['recordsFiltered'] !== 0 || $filtered['rows'] !== []) throw new RuntimeException('Payment directory search ignored.');
foreach ($repository->listReferences() as $contract) {
    $rows = $repository->dataTable($validator->validate(['subcontract_id'=>(string)$contract['id']], null));
    foreach ($rows['rows'] as $row) {
        if ((int)$row['subcontract_id'] !== (int)$contract['id']) throw new RuntimeException('Payment directory leaked a different contract.');
    }
    break;
}
