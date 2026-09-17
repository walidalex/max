<?php

declare(strict_types=1);

namespace App\Modules\ClientReceipts\Validators;

use App\Core\Exceptions\ValidationException;
use App\Core\Validation\Validator;
use App\Modules\ClientReceipts\DTOs\ClientReceiptData;

final class ClientReceiptValidator
{
    public function __construct(private readonly Validator $validator) {}

    public function validate(array $input, int $contractId): ClientReceiptData
    {
        if (
            !$this->validator->validate($input, [
                "receipt_date" => "required",
                "receipt_number" => "string|max:100",
                "payment_method" => "required",
                "reference_number" => "string|max:190",
                "notes" => "string|max:5000",
            ])
        ) {
            throw new ValidationException($this->validator->errors());
        }

        $errors = [];
        $date = $this->date($input["receipt_date"] ?? null);
        $amount = $this->decimal($input["amount"] ?? null);
        $method = (string) ($input["payment_method"] ?? "");
        if ($date === null) {
            $errors["receipt_date"][] = "تاريخ سند القبض غير صالح.";
        }
        if ($amount === null || $this->isZero($amount)) {
            $errors["amount"][] =
                "مبلغ سند القبض يجب أن يكون أكبر من صفر وبحد أقصى منزلتين عشريتين.";
        }
        if (
            !in_array(
                $method,
                ["cash", "bank_transfer", "cheque", "other"],
                true,
            )
        ) {
            $errors["payment_method"][] = "طريقة الدفع غير صالحة.";
        }
        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return new ClientReceiptData(
            $contractId,
            $date,
            $this->nullable($input["receipt_number"] ?? null),
            $amount,
            $method,
            $this->nullable($input["reference_number"] ?? null),
            $this->nullable($input["notes"] ?? null),
        );
    }

    private function decimal(mixed $value): ?string
    {
        $value = trim((string) $value);
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

    private function date(mixed $value): ?string
    {
        $value = trim((string) $value);
        $date = \DateTimeImmutable::createFromFormat("!Y-m-d", $value);
        return $date && $date->format("Y-m-d") === $value ? $value : null;
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);
        return $value === "" ? null : $value;
    }
}
