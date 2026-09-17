<?php declare(strict_types=1);
namespace App\Modules\ClientProgressStatements\Validators;
use App\Modules\ClientProgressStatements\DTOs\ClientProgressStatementTableQuery;
final class ClientProgressStatementTableQueryValidator
{
    public function validate(
        array $i,
        ?int $c = null,
    ): ClientProgressStatementTableQuery {
        $s = in_array(
            $i["status"] ?? null,
            ["draft", "approved", "cancelled"],
            true,
        )
            ? (string) $i["status"]
            : null;
        $d =
            strtolower((string) ($i["order"][0]["dir"] ?? "desc")) === "asc"
                ? "ASC"
                : "DESC";
        $cols = [
            "statement_code",
            "statement_number",
            "statement_sequence",
            "statement_date",
            "client_name",
            "project_name",
            "contract_code",
            "pricing_method",
            "current_cost_amount",
            "current_markup_amount",
            "current_variation_amount",
            "current_statement_amount",
            "cumulative_statement_amount",
            "status",
            "approved_by_name",
        ];
        $col =
            (string) ($i["columns"][(int) ($i["order"][0]["column"] ?? 0)][
                "data"
            ] ?? "statement_date");
        return new ClientProgressStatementTableQuery(
            (int) ($i["draw"] ?? 0),
            max(0, (int) ($i["start"] ?? 0)),
            min(100, max(10, (int) ($i["length"] ?? 10))),
            trim((string) ($i["search"]["value"] ?? "")),
            in_array($col, $cols, true) ? $col : "statement_date",
            $d,
            $c,
            (int) ($i["project_id"] ?? 0) ?: null,
            (int) ($i["client_id"] ?? 0) ?: null,
            $s,
            $this->date($i["date_from"] ?? null),
            $this->date($i["date_to"] ?? null),
        );
    }
    private function date(mixed $v): ?string
    {
        $v = trim((string) $v);
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : null;
    }
}
