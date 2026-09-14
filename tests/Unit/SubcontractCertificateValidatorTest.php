<?php

declare(strict_types=1);

use App\Core\Exceptions\ValidationException;
use App\Modules\SubcontractCertificates\Validators\SubcontractCertificateValidator;

$validator = new SubcontractCertificateValidator();
$lump = $validator->validate(['certificate_date' => '2026-09-14', 'current_progress_percentage' => '5.25'], 7, 'lump_sum');
if ($lump->currentProgressPercentage !== '5.2500' || $lump->quantities !== []) throw new RuntimeException('Lump-sum certificate validation failed.');
$boq = $validator->validate(['certificate_date' => '2026-09-14', 'quantities' => ['9' => '2.125']], 7, 'boq');
if (($boq->quantities[9] ?? null) !== '2.1250' || $boq->currentProgressPercentage !== null) throw new RuntimeException('BOQ certificate validation failed.');
foreach ([
    [['certificate_date' => 'invalid', 'current_progress_percentage' => '5'], 'lump_sum'],
    [['certificate_date' => '2026-09-14', 'current_progress_percentage' => '100.1'], 'lump_sum'],
    [['certificate_date' => '2026-09-14', 'quantities' => ['9' => '-1']], 'boq'],
    [['certificate_date' => '2026-09-14', 'period_from' => '2026-09-15', 'period_to' => '2026-09-14', 'quantities' => []], 'boq'],
] as [$input, $method]) {
    try { $validator->validate($input, 7, $method); throw new RuntimeException('Invalid certificate data accepted.'); } catch (ValidationException) {}
}
