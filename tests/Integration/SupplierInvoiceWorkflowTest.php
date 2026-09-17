<?php

declare(strict_types=1);

use App\Core\Auth\Auth;
use App\Core\Database\Database;
use App\Core\Exceptions\BusinessRuleException;
use App\Modules\SupplierInvoices\DTOs\SupplierInvoiceData;
use App\Modules\SupplierInvoices\Repositories\SupplierInvoiceRepository;
use App\Modules\SupplierInvoices\Services\SupplierInvoiceService;

/** @var Database $db */
$db = $app->make(Database::class);
/** @var SupplierInvoiceService $service */
$service = $app->make(SupplierInvoiceService::class);
/** @var SupplierInvoiceRepository $repository */
$repository = $app->make(SupplierInvoiceRepository::class);
/** @var Auth $auth */
$auth = $app->make(Auth::class);

$user = $db->execute('SELECT id, name, username FROM users WHERE is_active = 1 LIMIT 1')->fetch_assoc();
$code = $db->execute('SELECT id FROM cost_codes WHERE is_active = 1 LIMIT 1')->fetch_assoc();
if (!$user || !$code) {
    throw new RuntimeException('Supplier invoice fixtures unavailable.');
}

$suffix = (string) random_int(100000, 999999);
$clientId = $vendorId = $projectId = $otherProjectId = 0;
$invoiceIds = [];

try {
    $db->execute("INSERT INTO clients(client_code, client_type, name) VALUES (?, 'individual', 'Invoice Client')", ['SIC-' . $suffix]);
    $clientId = (int) $db->connection()->insert_id;
    $db->execute("INSERT INTO vendors(vendor_code, vendor_type, name) VALUES (?, 'supplier', 'Invoice Supplier')", ['SIV-' . $suffix]);
    $vendorId = (int) $db->connection()->insert_id;
    $db->execute("INSERT INTO projects(project_code, name, client_id, project_type, status) VALUES (?, 'Invoice Project', ?, 'contracting', 'active')", ['SIP-' . $suffix, $clientId]);
    $projectId = (int) $db->connection()->insert_id;
    $db->execute("INSERT INTO projects(project_code, name, client_id, project_type, status) VALUES (?, 'Other Invoice Project', ?, 'contracting', 'active')", ['SIP-OTHER-' . $suffix, $clientId]);
    $otherProjectId = (int) $db->connection()->insert_id;
    $auth->login($user);

    $data = new SupplierInvoiceData($vendorId, $projectId, 'INV-01', '2026-09-01', '2026-09-30', null, null, [
        ['description' => 'A', 'cost_code_id' => (int) $code['id'], 'amount' => '0.10', 'sort_order' => 1],
        ['description' => 'B', 'cost_code_id' => (int) $code['id'], 'amount' => '0.20', 'sort_order' => 2],
    ]);
    $invoiceId = $service->save($data);
    $invoiceIds[] = $invoiceId;

    $staleProjectId = $projectId;
    $db->execute('UPDATE supplier_invoices SET project_id = ? WHERE id = ?', [$otherProjectId, $invoiceId]);
    $db->transaction(function () use ($repository, $invoiceId, $staleProjectId): void {
        $repository->project($staleProjectId, true);
        if ($repository->findForProject($invoiceId, $staleProjectId, true) !== null) {
            throw new RuntimeException('Stale supplier invoice project was accepted after locking.');
        }
    });
    $db->execute('UPDATE supplier_invoices SET project_id = ? WHERE id = ?', [$projectId, $invoiceId]);

    try {
        $service->save($data);
        throw new RuntimeException('Duplicate invoice number accepted.');
    } catch (BusinessRuleException) {
    }

    $service->approve($invoiceId);
    $invoice = $service->find($invoiceId);
    if ($invoice['status'] !== 'approved' || $invoice['total_amount'] !== '0.30') {
        throw new RuntimeException('Exact supplier invoice total failed.');
    }
    $costs = $db->execute("SELECT COUNT(*) n, CAST(SUM(amount) AS DECIMAL(18,2)) total FROM project_actual_costs WHERE source_type = 'supplier_invoice' AND source_id = ?", [$invoiceId])->fetch_assoc();
    if ((int) $costs['n'] !== 2 || $costs['total'] !== '0.30') {
        throw new RuntimeException('Supplier invoice costs were not recognized exactly once.');
    }

    try {
        $service->approve($invoiceId);
        throw new RuntimeException('Double approval accepted.');
    } catch (BusinessRuleException) {
    }
    try {
        $service->save(new SupplierInvoiceData($vendorId, $projectId, 'INV-01', '2026-09-01', null, null, null, [
            ['description' => 'X', 'cost_code_id' => (int) $code['id'], 'amount' => '1.00', 'sort_order' => 1],
        ]), $invoiceId);
        throw new RuntimeException('Approved invoice was mutable.');
    } catch (BusinessRuleException) {
    }

    $cancelledId = $service->save(new SupplierInvoiceData($vendorId, $projectId, 'INV-CANCEL', '2026-09-02', null, null, null, [
        ['description' => 'C', 'cost_code_id' => (int) $code['id'], 'amount' => '1.00', 'sort_order' => 1],
    ]));
    $invoiceIds[] = $cancelledId;
    $service->cancel($cancelledId);
    $effect = $db->execute("SELECT COUNT(*) n FROM project_actual_costs WHERE source_type = 'supplier_invoice' AND source_id = ?", [$cancelledId])->fetch_assoc();
    if ((int) $effect['n'] !== 0) {
        throw new RuntimeException('Cancelled invoice had financial effect.');
    }
} finally {
    $auth->logout();
    foreach ($invoiceIds as $invoiceId) {
        $db->execute("DELETE FROM project_actual_costs WHERE source_type = 'supplier_invoice' AND source_id = ?", [$invoiceId]);
        $db->execute('DELETE FROM supplier_invoice_lines WHERE supplier_invoice_id = ?', [$invoiceId]);
        $db->execute('DELETE FROM supplier_invoices WHERE id = ?', [$invoiceId]);
    }
    if ($projectId) {
        $db->execute('DELETE FROM projects WHERE id = ?', [$projectId]);
    }
    if ($otherProjectId) {
        $db->execute('DELETE FROM projects WHERE id = ?', [$otherProjectId]);
    }
    if ($vendorId) {
        $db->execute('DELETE FROM vendors WHERE id = ?', [$vendorId]);
    }
    if ($clientId) {
        $db->execute('DELETE FROM clients WHERE id = ?', [$clientId]);
    }
}
