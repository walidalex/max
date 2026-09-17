<?php

declare(strict_types=1);

use App\Core\Auth\Auth;
use App\Core\Database\Database;
use App\Core\Exceptions\BusinessRuleException;
use App\Modules\ClientReceipts\DTOs\ClientReceiptAllocationData;
use App\Modules\ClientReceipts\DTOs\ClientReceiptData;
use App\Modules\ClientReceipts\Services\ClientReceiptAllocationService;
use App\Modules\ClientReceipts\Services\ClientReceiptService;

/** @var Database $db */ $db = $app->make(Database::class);
/** @var Auth $auth */ $auth = $app->make(Auth::class);
/** @var ClientReceiptService $receipts */ $receipts = $app->make(
    ClientReceiptService::class,
);
/** @var ClientReceiptAllocationService $allocations */ $allocations = $app->make(
    ClientReceiptAllocationService::class,
);
$user = $db
    ->execute("SELECT id,name,username FROM users WHERE is_active=1 LIMIT 1")
    ->fetch_assoc();
if (!$user) {
    throw new RuntimeException("Receipt user fixture unavailable.");
}
$suffix = (string) random_int(100000, 999999);
$client1 = $client2 = $project1 = $project2 = $contract1 = $contract2 = $statement1 = $statement2 = $statement3 = $receipt1 = $advance = $draft = 0;
try {
    $db->execute(
        "INSERT INTO clients(client_code,client_type,name)VALUES(?,'individual','Receipt Client 1')",
        ["RCL-" . $suffix],
    );
    $client1 = (int) $db->connection()->insert_id;
    $db->execute(
        "INSERT INTO clients(client_code,client_type,name)VALUES(?,'individual','Receipt Client 2')",
        ["RC2-" . $suffix],
    );
    $client2 = (int) $db->connection()->insert_id;
    $db->execute(
        "INSERT INTO projects(project_code,name,client_id,project_type,status)VALUES(?,'Receipt Project 1',?,'contracting','active')",
        ["RP1-" . $suffix, $client1],
    );
    $project1 = (int) $db->connection()->insert_id;
    $db->execute(
        "INSERT INTO projects(project_code,name,client_id,project_type,status)VALUES(?,'Receipt Project 2',?,'contracting','active')",
        ["RP2-" . $suffix, $client2],
    );
    $project2 = (int) $db->connection()->insert_id;
    $db->execute(
        "INSERT INTO client_contracts(contract_code,contract_number,project_id,client_id,pricing_method,contract_date,markup_percentage,title,status)VALUES(?,?,?,?,'cost_plus','2026-01-01','10.0000','Receipt Contract 1','active')",
        ["RCT1-" . $suffix, "RN1-" . $suffix, $project1, $client1],
    );
    $contract1 = (int) $db->connection()->insert_id;
    $db->execute(
        "INSERT INTO client_contracts(contract_code,contract_number,project_id,client_id,pricing_method,contract_date,markup_percentage,title,status)VALUES(?,?,?,?,'cost_plus','2026-01-01','10.0000','Receipt Contract 2','active')",
        ["RCT2-" . $suffix, "RN2-" . $suffix, $project2, $client2],
    );
    $contract2 = (int) $db->connection()->insert_id;
    $auth->login($user);
    $advance = $receipts->save(
        new ClientReceiptData(
            $contract2,
            "2026-01-01",
            null,
            "60.00",
            "cash",
            null,
            null,
        ),
    );
    $receipts->post($advance);
    $advanceSummary = $receipts->financialSummary($contract2);
    if (
        $advanceSummary["approved_claims"] !== "0.00" ||
        $advanceSummary["unallocated_receipts"] !== "60.00"
    ) {
        throw new RuntimeException("Advance receipt without claims failed.");
    }
    foreach (
        [
            [
                $contract1,
                $project1,
                $client1,
                "RS1-" . $suffix,
                "2026-02-15",
                "100.00",
            ],
            [
                $contract1,
                $project1,
                $client1,
                "RS2-" . $suffix,
                "2025-12-20",
                "50.00",
            ],
            [
                $contract2,
                $project2,
                $client2,
                "RS3-" . $suffix,
                "2026-02-01",
                "50.00",
            ],
        ]
        as $index => $row
    ) {
        $db->execute(
            "INSERT INTO client_progress_statements(statement_code,client_contract_id,project_id,client_id,pricing_method_snapshot,client_name_snapshot,project_code_snapshot,project_name_snapshot,contract_code_snapshot,contract_title_snapshot,statement_date,statement_sequence,markup_percentage_snapshot,previous_cost_amount,current_cost_amount,cumulative_cost_amount,previous_markup_amount,current_markup_amount,cumulative_markup_amount,previous_variation_amount,current_variation_amount,cumulative_variation_amount,previous_statement_amount,current_statement_amount,cumulative_statement_amount,status,created_by,approved_at,approved_by)VALUES(?,?,?,?,'cost_plus','Client','Project','Project','Contract','Contract',?,?,'10.0000','0.00','0.00','0.00','0.00','0.00','0.00','0.00','0.00','0.00','0.00',?,?,'approved',?,NOW(),?)",
            [
                $row[3],
                $row[0],
                $row[1],
                $row[2],
                $row[4],
                $index + 1,
                $row[5],
                $row[5],
                (int) $user["id"],
                (int) $user["id"],
            ],
        );
        ${"statement" . ($index + 1)} = (int) $db->connection()->insert_id;
    }
    $allocations->allocate(
        $advance,
        new ClientReceiptAllocationData([
            [
                "statement_id" => $statement3,
                "allocated_amount" => "10.00",
                "notes" => null,
            ],
        ]),
    );
    if ($receipts->getReceiptUnallocatedAmount($advance) !== "50.00") {
        throw new RuntimeException(
            "Future statement allocation from advance receipt failed.",
        );
    }
    $receipt1 = $receipts->save(
        new ClientReceiptData(
            $contract1,
            "2026-01-01",
            null,
            "120.00",
            "bank_transfer",
            null,
            null,
        ),
    );
    $anotherNull = $receipts->save(
        new ClientReceiptData(
            $contract1,
            "2026-01-02",
            null,
            "1.00",
            "cash",
            null,
            null,
        ),
    );
    $receipts->cancel($anotherNull);
    try {
        $receipts->save(
            new ClientReceiptData(
                $contract1,
                "2027-01-01",
                null,
                "120.00",
                "cash",
                null,
                null,
            ),
            $receipt1,
        );
        throw new RuntimeException("Cross-year receipt edit accepted.");
    } catch (BusinessRuleException) {
    }
    $receipts->post($receipt1);
    $posted = $receipts->find($receipt1);
    if (
        $posted["status"] !== "posted" ||
        $posted["contract_number_snapshot"] !== "RN1-" . $suffix ||
        $posted["posted_by"] === null
    ) {
        throw new RuntimeException("Receipt posting snapshot/audit failed.");
    }
    try {
        $receipts->save(
            new ClientReceiptData(
                $contract1,
                "2026-01-01",
                null,
                "121.00",
                "cash",
                null,
                null,
            ),
            $receipt1,
        );
        throw new RuntimeException("Posted receipt edit accepted.");
    } catch (BusinessRuleException) {
    }
    try {
        $receipts->cancel($receipt1);
        throw new RuntimeException("Posted receipt cancellation accepted.");
    } catch (BusinessRuleException) {
    }
    $allocations->allocate(
        $receipt1,
        new ClientReceiptAllocationData([
            [
                "statement_id" => $statement1,
                "allocated_amount" => "20.00",
                "notes" => null,
            ],
            [
                "statement_id" => $statement2,
                "allocated_amount" => "30.00",
                "notes" => null,
            ],
        ]),
    );
    $allocations->allocate(
        $receipt1,
        new ClientReceiptAllocationData([
            [
                "statement_id" => $statement1,
                "allocated_amount" => "15.00",
                "notes" => null,
            ],
        ]),
    );
    if (
        $receipts->getReceiptAllocatedTotal($receipt1) !== "65.00" ||
        $receipts->getReceiptUnallocatedAmount($receipt1) !== "55.00" ||
        $receipts->getStatementOutstandingAmount($statement1) !== "65.00"
    ) {
        throw new RuntimeException("Receipt allocation balances failed.");
    }
    try {
        $allocations->allocate(
            $receipt1,
            new ClientReceiptAllocationData([
                [
                    "statement_id" => $statement1,
                    "allocated_amount" => "70.00",
                    "notes" => null,
                ],
            ]),
        );
        throw new RuntimeException("Statement overpayment accepted.");
    } catch (BusinessRuleException) {
    }
    try {
        $allocations->allocate(
            $receipt1,
            new ClientReceiptAllocationData([
                [
                    "statement_id" => $statement1,
                    "allocated_amount" => "50.00",
                    "notes" => null,
                ],
                [
                    "statement_id" => $statement2,
                    "allocated_amount" => "10.00",
                    "notes" => null,
                ],
            ]),
        );
        throw new RuntimeException("Receipt over-allocation batch accepted.");
    } catch (BusinessRuleException) {
    }
    if ($receipts->getReceiptAllocatedTotal($receipt1) !== "65.00") {
        throw new RuntimeException("Failed batch was not atomic.");
    }
    try {
        $allocations->allocate(
            $receipt1,
            new ClientReceiptAllocationData([
                [
                    "statement_id" => $statement3,
                    "allocated_amount" => "1.00",
                    "notes" => null,
                ],
            ]),
        );
        throw new RuntimeException("Cross-contract allocation accepted.");
    } catch (BusinessRuleException) {
    }
    $draft = $receipts->save(
        new ClientReceiptData(
            $contract1,
            "2026-03-01",
            "B-" . $suffix,
            "5.00",
            "cash",
            null,
            null,
        ),
    );
    try {
        $receipts->save(
            new ClientReceiptData(
                $contract1,
                "2026-03-02",
                "B-" . $suffix,
                "5.00",
                "cash",
                null,
                null,
            ),
        );
        throw new RuntimeException("Duplicate receipt number accepted.");
    } catch (BusinessRuleException) {
    }
    $db->execute("UPDATE client_contracts SET status='cancelled' WHERE id=?", [
        $contract1,
    ]);
    try {
        $receipts->save(
            new ClientReceiptData(
                $contract1,
                "2026-03-02",
                null,
                "5.00",
                "cash",
                null,
                null,
            ),
        );
        throw new RuntimeException("Receipt created for cancelled contract.");
    } catch (BusinessRuleException) {
    }
    try {
        $receipts->post($draft);
        throw new RuntimeException("Draft receipt posted for cancelled contract.");
    } catch (BusinessRuleException) {
    }
    $receipts->cancel($draft);
    $allocations->allocate(
        $receipt1,
        new ClientReceiptAllocationData([
            [
                "statement_id" => $statement1,
                "allocated_amount" => "5.00",
                "notes" => null,
            ],
        ]),
    );
    $summary = $receipts->financialSummary($contract1);
    if (
        $summary["approved_claims"] !== "150.00" ||
        $summary["posted_receipts"] !== "120.00" ||
        $summary["allocated_receipts"] !== "70.00" ||
        $summary["statement_outstanding"] !== "80.00" ||
        $summary["unallocated_receipts"] !== "50.00" ||
        $summary["inconsistent"]
    ) {
        throw new RuntimeException("Contract receipt summary failed.");
    }
    $db->execute(
        "INSERT INTO client_receipt_allocations(receipt_id,client_progress_statement_id,allocated_amount,statement_code_snapshot,statement_number_snapshot,statement_sequence_snapshot,statement_date_snapshot,statement_amount_snapshot,allocated_at,allocated_by) SELECT ?,s.id,'1.00',s.statement_code,s.statement_number,s.statement_sequence,s.statement_date,s.current_statement_amount,NOW(),? FROM client_progress_statements s WHERE s.id=?",
        [$receipt1, (int) $user["id"], $statement3],
    );
    $corruptAllocation = (int) $db->connection()->insert_id;
    if (!$receipts->financialSummary($contract1)["inconsistent"]) {
        throw new RuntimeException(
            "Cross-contract allocation corruption was not detected.",
        );
    }
    try {
        $allocations->allocate(
            $receipt1,
            new ClientReceiptAllocationData([
                [
                    "statement_id" => $statement1,
                    "allocated_amount" => "1.00",
                    "notes" => null,
                ],
            ]),
        );
        throw new RuntimeException(
            "Allocation continued despite inconsistent history.",
        );
    } catch (BusinessRuleException) {
    }
    $db->execute("DELETE FROM client_receipt_allocations WHERE id=?", [
        $corruptAllocation,
    ]);
} finally {
    $auth->logout();
    if ($contract1 || $contract2) {
        $db->execute(
            "DELETE a FROM client_receipt_allocations a JOIN client_receipts r ON r.id=a.receipt_id WHERE r.client_contract_id IN(?,?)",
            [$contract1, $contract2],
        );
        $db->execute(
            "DELETE FROM client_receipts WHERE client_contract_id IN(?,?)",
            [$contract1, $contract2],
        );
        $db->execute(
            "DELETE FROM client_progress_statements WHERE client_contract_id IN(?,?)",
            [$contract1, $contract2],
        );
    }
    $contract1 &&
        $db->execute("DELETE FROM client_contracts WHERE id=?", [$contract1]);
    $contract2 &&
        $db->execute("DELETE FROM client_contracts WHERE id=?", [$contract2]);
    $project1 && $db->execute("DELETE FROM projects WHERE id=?", [$project1]);
    $project2 && $db->execute("DELETE FROM projects WHERE id=?", [$project2]);
    $client1 && $db->execute("DELETE FROM clients WHERE id=?", [$client1]);
    $client2 && $db->execute("DELETE FROM clients WHERE id=?", [$client2]);
}
