<?php declare(strict_types=1);
namespace App\Modules\ClientProgressStatements\Validators;
use App\Core\Exceptions\ValidationException;
use App\Core\Validation\Validator;
use App\Modules\ClientProgressStatements\DTOs\ClientProgressStatementData;
final class ClientProgressStatementValidator
{
    public function __construct(private readonly Validator $validator) {}
    public function validate(array $i): ClientProgressStatementData
    {
        if (
            !$this->validator->validate($i, [
                "statement_date" => "required|string|max:10",
                "period_from" => "string|max:10",
                "period_to" => "string|max:10",
                "statement_number" => "string|max:100",
                "notes" => "string|max:5000",
            ])
        ) {
            throw new ValidationException($this->validator->errors());
        }
        $e = [];
        $date = $this->date(
            (string) ($i["statement_date"] ?? ""),
            "statement_date",
            $e,
            false,
        );
        $from = $this->date(
            (string) ($i["period_from"] ?? ""),
            "period_from",
            $e,
            true,
        );
        $to = $this->date(
            (string) ($i["period_to"] ?? ""),
            "period_to",
            $e,
            true,
        );
        if ($from !== null && $to !== null && $to < $from) {
            $e["period_to"][] = "تاريخ نهاية الفترة يجب ألا يسبق بدايتها.";
        }
        $costs = [];
        foreach ((array) ($i["cost_ids"] ?? []) as $id) {
            $id = (int) $id;
            if ($id < 1) {
                $e["cost_ids"][] = "اختيار التكلفة غير صالح.";
            } else {
                $costs[$id] = $id;
            }
        }
        $vars = [];
        foreach ((array) ($i["variations"] ?? []) as $id => $amount) {
            $id = (int) $id;
            $amount = trim((string) $amount);
            if ($amount === "") {
                continue;
            }
            if (
                $id < 1 ||
                !preg_match(
                    '/^(?!0+(?:\.0{1,2})?$)\d{1,16}(?:\.\d{1,2})?$/',
                    $amount,
                )
            ) {
                $e["variations"][] = "مبلغ العمل الإضافي غير صالح.";
                continue;
            }
            $vars[$id] = [
                "variation_id" => $id,
                "current_billed_amount" => $amount,
            ];
        }
        if ($e) {
            throw new ValidationException($e);
        }
        $n = static fn(mixed $v): ?string => trim((string) $v) === ""
            ? null
            : trim((string) $v);
        return new ClientProgressStatementData(
            (string) $date,
            $from,
            $to,
            $n($i["statement_number"] ?? ""),
            $n($i["notes"] ?? ""),
            array_values($costs),
            array_values($vars),
        );
    }
    private function date(string $v, string $f, array &$e, bool $n): ?string
    {
        $v = trim($v);
        if ($n && $v === "") {
            return null;
        }
        $d = \DateTimeImmutable::createFromFormat("!Y-m-d", $v);
        if (!$d || $d->format("Y-m-d") !== $v) {
            $e[$f][] = "التاريخ غير صالح.";
            return $n ? null : $v;
        }
        return $v;
    }
}
