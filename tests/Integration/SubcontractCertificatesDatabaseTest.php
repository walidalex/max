<?php

declare(strict_types=1);

use App\Core\Database\Database;
use App\Modules\SubcontractCertificates\Repositories\SubcontractCertificateRepository;

/** @var Database $db */
$db = $app->make(Database::class);
$result = $db->execute("SELECT COUNT(*) total FROM permissions WHERE module='subcontract_certificates'");
$row = $result instanceof mysqli_result ? $result->fetch_assoc() : [];
if ((int) ($row['total'] ?? 0) !== 5) throw new RuntimeException('Certificate permissions missing.');
$result = $db->execute("SELECT COUNT(*) total FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='subcontract_progress_certificates' AND column_name IN('approved_at','approved_by','cancelled_at','cancelled_by','previous_earned_value','current_earned_value','cumulative_earned_value')");
$row = $result instanceof mysqli_result ? $result->fetch_assoc() : [];
if ((int) ($row['total'] ?? 0) !== 7) throw new RuntimeException('Certificate audit or earned-value schema missing.');
$result = $db->execute("SELECT COUNT(*) total FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='subcontract_progress_certificates' AND index_name='uq_subcontract_progress_number' AND non_unique=0");
$row = $result instanceof mysqli_result ? $result->fetch_assoc() : [];
if ((int) ($row['total'] ?? 0) !== 2) throw new RuntimeException('Scoped certificate number key missing.');
$result = $db->execute("SELECT COUNT(*) total FROM information_schema.referential_constraints WHERE constraint_schema=DATABASE() AND table_name IN('subcontract_progress_certificates','subcontract_progress_items') AND delete_rule='RESTRICT'");
$row = $result instanceof mysqli_result ? $result->fetch_assoc() : [];
if ((int) ($row['total'] ?? 0) < 5) throw new RuntimeException('Certificate restrictive foreign keys missing.');
/** @var SubcontractCertificateRepository $repository */
$repository = $app->make(SubcontractCertificateRepository::class);
if (!$repository->lumpProgressExceeds('99.9999', '0.0002')) throw new RuntimeException('Percentage ceiling calculation failed.');
if ($repository->lumpProgressExceeds('95.0000', '5.0000')) throw new RuntimeException('Exact 100 percent was rejected.');
