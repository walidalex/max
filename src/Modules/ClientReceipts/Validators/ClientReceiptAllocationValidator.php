<?php

declare(strict_types=1);

namespace App\Modules\ClientReceipts\Validators;

use App\Core\Exceptions\ValidationException;
use App\Modules\ClientReceipts\DTOs\ClientReceiptAllocationData;

final class ClientReceiptAllocationValidator
{
    public function validate(array $input): ClientReceiptAllocationData
    {
        $rows = $input["allocations"] ?? null;
        if (!is_array($rows)) {
            throw new ValidationException([
                "allocations" => ["يجب إدخال تخصيص واحد على الأقل."],
            ]);
        }
        $allocations = [];
        $seen = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $rawAmount = trim((string) ($row["allocated_amount"] ?? ""));
            if ($rawAmount === "") {
                continue;
            }
            $id = filter_var(
                $row["statement_id"] ?? null,
                FILTER_VALIDATE_INT,
                ["options" => ["min_range" => 1]],
            );
            $amount = $this->decimal($rawAmount);
            if ($id === false) {
                throw new ValidationException([
                    "allocations" => ["المستخلص المحدد غير صالح."],
                ]);
            }
            if (isset($seen[(int) $id])) {
                throw new ValidationException([
                    "allocations" => [
                        "لا يمكن تكرار المستخلص داخل نفس عملية التخصيص.",
                    ],
                ]);
            }
            if ($amount === null || $this->isZero($amount)) {
                throw new ValidationException([
                    "allocations" => [
                        "مبلغ التخصيص يجب أن يكون أكبر من صفر وبحد أقصى منزلتين عشريتين.",
                    ],
                ]);
            }
            $seen[(int) $id] = true;
            $notes = trim((string) ($row["notes"] ?? ""));
            if (strlen($notes) > 5000) {
                throw new ValidationException([
                    "allocations" => ["ملاحظات التخصيص طويلة جداً."],
                ]);
            }
            $allocations[] = [
                "statement_id" => (int) $id,
                "allocated_amount" => $amount,
                "notes" => $notes === "" ? null : $notes,
            ];
        }
        if ($allocations === []) {
            throw new ValidationException([
                "allocations" => ["يجب إدخال مبلغ تخصيص واحد على الأقل."],
            ]);
        }
        usort(
            $allocations,
            static fn(array $a, array $b): int => $a["statement_id"] <=>
                $b["statement_id"],
        );
        return new ClientReceiptAllocationData($allocations);
    }

    private function decimal(string $value): ?string
    {
        if (!preg_match('/^(\d{1,16})(?:\.(\d{1,2}))?$/', $value, $matches)) {
            return null;
        }
        $whole = ltrim($matches[1], "0") ?: "0";
        return $whole . "." . str_pad($matches[2] ?? "", 2, "0");
    }

    private function isZero(string $value): bool
    {
        return trim(str_replace(["0", "."], "", $value)) === "";
    }
}
