<?php

declare(strict_types=1);

namespace App\Modules\ClientReceipts\Validators;

use App\Modules\ClientReceipts\DTOs\ClientReceiptTableQuery;

final class ClientReceiptTableQueryValidator
{
    public function validate(
        array $input,
        ?int $contractId = null,
    ): ClientReceiptTableQuery {
        $columns = [
            "receipt_code",
            "receipt_number",
            "receipt_date",
            "client_name",
            "project_name",
            "contract_code",
            "amount",
            "allocated",
            "unallocated",
            "payment_method",
            "reference_number",
            "status",
            "posted_by_name",
        ];
        $column =
            (string) ($input["columns"][
                (int) ($input["order"][0]["column"] ?? 0)
            ]["data"] ?? "receipt_date");
        $status = in_array(
            $input["status"] ?? null,
            ["draft", "posted", "cancelled"],
            true,
        )
            ? (string) $input["status"]
            : null;
        $method = in_array(
            $input["payment_method"] ?? null,
            ["cash", "bank_transfer", "cheque", "other"],
            true,
        )
            ? (string) $input["payment_method"]
            : null;
        return new ClientReceiptTableQuery(
            (int) ($input["draw"] ?? 0),
            max(0, (int) ($input["start"] ?? 0)),
            min(100, max(10, (int) ($input["length"] ?? 10))),
            trim((string) ($input["search"]["value"] ?? "")),
            in_array($column, $columns, true) ? $column : "receipt_date",
            strtolower((string) ($input["order"][0]["dir"] ?? "desc")) === "asc"
                ? "ASC"
                : "DESC",
            $contractId ?? ((int) ($input["contract_id"] ?? 0) ?: null),
            (int) ($input["project_id"] ?? 0) ?: null,
            (int) ($input["client_id"] ?? 0) ?: null,
            $status,
            $method,
            $this->date($input["date_from"] ?? null),
            $this->date($input["date_to"] ?? null),
        );
    }

    private function date(mixed $value): ?string
    {
        $value = trim((string) $value);
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
    }
}
