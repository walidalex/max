<?php

declare(strict_types=1);

use App\Core\Database\Database;

/** @var Database $database */
$database = $app->make(Database::class);
$schema = (string) env("DB_DATABASE");
$tables = $database->execute(
    "SELECT table_name FROM information_schema.tables WHERE table_schema=? AND table_name IN('client_receipts','client_receipt_allocations')",
    [$schema],
);
if (
    !($tables instanceof mysqli_result) ||
    count($tables->fetch_all(MYSQLI_ASSOC)) !== 2
) {
    throw new RuntimeException("Client receipt tables are missing.");
}
$columns = $database->execute(
    "SELECT column_name,is_nullable,column_type FROM information_schema.columns WHERE table_schema=? AND table_name='client_receipts'",
    [$schema],
);
$map = [];
foreach ($columns->fetch_all(MYSQLI_ASSOC) as $column) {
    $map[$column["column_name"]] = $column;
}
foreach (
    [
        "receipt_code",
        "receipt_number",
        "client_contract_id",
        "project_id",
        "client_id",
        "amount",
        "status",
        "posted_at",
        "posted_by",
        "cancelled_at",
        "cancelled_by",
        "contract_number_snapshot",
        "contract_title_snapshot",
    ]
    as $column
) {
    if (!isset($map[$column])) {
        throw new RuntimeException(
            "Missing client_receipts column: " . $column,
        );
    }
}
if (
    $map["amount"]["column_type"] !== "decimal(18,2)" ||
    $map["contract_number_snapshot"]["is_nullable"] !== "YES" ||
    $map["contract_title_snapshot"]["is_nullable"] !== "YES"
) {
    throw new RuntimeException(
        "Client receipt DECIMAL or snapshot nullability is invalid.",
    );
}
$indexes = $database->execute(
    "SELECT index_name,GROUP_CONCAT(column_name ORDER BY seq_in_index) columns_list FROM information_schema.statistics WHERE table_schema=? AND table_name='client_receipts' GROUP BY index_name",
    [$schema],
);
$indexMap = [];
foreach ($indexes->fetch_all(MYSQLI_ASSOC) as $index) {
    $indexMap[$index["index_name"]] = $index["columns_list"];
}
if (
    ($indexMap["uq_client_receipts_number"] ?? null) !==
    "client_contract_id,receipt_number"
) {
    throw new RuntimeException("Receipt number uniqueness is invalid.");
}
$allocationIndexes = $database->execute(
    "SELECT index_name FROM information_schema.statistics WHERE table_schema=? AND table_name='client_receipt_allocations'",
    [$schema],
);
$names = array_column(
    $allocationIndexes->fetch_all(MYSQLI_ASSOC),
    "index_name",
);
if (in_array("uq_client_receipt_allocations_receipt_statement", $names, true)) {
    throw new RuntimeException(
        "Allocation incorrectly prevents append-only top-ups.",
    );
}
$permissions = $database
    ->execute(
        "SELECT COUNT(*) total FROM permissions WHERE code LIKE 'client_receipts.%'",
    )
    ->fetch_assoc();
if ((int) $permissions["total"] !== 6) {
    throw new RuntimeException("Client receipt permissions are incomplete.");
}
