<?php
declare(strict_types=1);

use App\Core\Database\Database;
use App\Modules\Vendors\DTOs\VendorData;
use App\Modules\Vendors\Services\VendorService;

/** @var Database $database */
$database = $app->make(Database::class);
/** @var VendorService $service */
$service = $app->make(VendorService::class);
$sectionResult = $database->execute('SELECT id FROM work_sections WHERE is_active = 1 ORDER BY sort_order, id LIMIT 1');
$section = $sectionResult instanceof mysqli_result ? $sectionResult->fetch_assoc() : null;
if (!is_array($section)) { throw new RuntimeException('An active work section is required for the vendor workflow test.'); }
$sectionId = (int) $section['id'];
$vendorId = $service->save(new VendorData('subcontractor', 'اختبار مجالات المورد', null, null, null, null, null, null, null, [$sectionId]));
try {
    $links = $database->execute('SELECT work_section_id FROM vendor_work_sections WHERE vendor_id = ?', [$vendorId]);
    if (!($links instanceof mysqli_result) || $links->num_rows !== 1 || (int) $links->fetch_assoc()['work_section_id'] !== $sectionId) {
        throw new RuntimeException('Vendor work sections were not saved atomically.');
    }
    $service->save(new VendorData('supplier', 'اختبار مجالات المورد', null, null, null, null, null, null, null, []), $vendorId);
    $links = $database->execute('SELECT COUNT(*) AS total FROM vendor_work_sections WHERE vendor_id = ?', [$vendorId]);
    if (!($links instanceof mysqli_result) || (int) $links->fetch_assoc()['total'] !== 0) {
        throw new RuntimeException('Vendor work sections were not synchronized on update.');
    }
} finally {
    $database->execute('DELETE FROM vendors WHERE id = ?', [$vendorId]);
}
