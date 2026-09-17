<?php

declare(strict_types=1);

use App\Core\Exceptions\ValidationException;
use App\Modules\ClientReceipts\Validators\ClientReceiptAllocationValidator;
use App\Modules\ClientReceipts\Validators\ClientReceiptValidator;

$validator = $app->make(ClientReceiptValidator::class);
$data = $validator->validate(
    [
        "receipt_date" => "2026-01-01",
        "receipt_number" => "  ",
        "amount" => "000100.5",
        "payment_method" => "cash",
        "reference_number" => " ",
        "notes" => " ",
    ],
    7,
);
if (
    $data->receiptNumber !== null ||
    $data->referenceNumber !== null ||
    $data->notes !== null ||
    $data->amount !== "100.50"
) {
    throw new RuntimeException("Client receipt normalization failed.");
}
foreach (["0", "-1", "1e3", "1.001"] as $amount) {
    try {
        $validator->validate(
            [
                "receipt_date" => "2026-01-01",
                "amount" => $amount,
                "payment_method" => "cash",
            ],
            7,
        );
        throw new RuntimeException("Invalid receipt amount accepted.");
    } catch (ValidationException) {
    }
}
$allocationValidator = $app->make(ClientReceiptAllocationValidator::class);
try {
    $allocationValidator->validate([
        "allocations" => [
            ["statement_id" => 1, "allocated_amount" => "1.00"],
            ["statement_id" => 1, "allocated_amount" => "2.00"],
        ],
    ]);
    throw new RuntimeException("Duplicate statement IDs accepted.");
} catch (ValidationException) {
}
$allocation = $allocationValidator->validate([
    "allocations" => [
        ["statement_id" => 2, "allocated_amount" => "10.5", "notes" => " "],
        ["statement_id" => 1, "allocated_amount" => ""],
    ],
]);
if (
    count($allocation->allocations) !== 1 ||
    $allocation->allocations[0]["allocated_amount"] !== "10.50" ||
    $allocation->allocations[0]["notes"] !== null
) {
    throw new RuntimeException("Allocation normalization failed.");
}
