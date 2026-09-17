<?php
declare(strict_types=1);
namespace App\Modules\ClientProgressStatements\Repositories;
use App\Core\Database\Database;
use App\Modules\ClientProgressStatements\DTOs\ClientProgressStatementData;
use App\Modules\ClientProgressStatements\DTOs\ClientProgressStatementTableQuery;
final class ClientProgressStatementRepository
{
    public function __construct(private readonly Database $db) {}
    public function contract(int $id, bool $lock = false): ?array
    {
        return $this->one(
            "SELECT cc.id,cc.contract_code,cc.contract_number,cc.title,cc.project_id,cc.client_id,cc.pricing_method,cc.markup_percentage,cc.status,p.project_code,p.name project_name,COALESCE(c.company_name,c.name) client_name FROM client_contracts cc JOIN projects p ON p.id=cc.project_id JOIN clients c ON c.id=cc.client_id WHERE cc.id=?" .
                ($lock ? " FOR UPDATE" : ""),
            [$id],
        );
    }
    public function find(int $id): ?array
    {
        return $this->one(
            "SELECT s.*,cc.contract_code current_contract_code,cc.contract_number current_contract_number,cc.title current_contract_title,cc.pricing_method,p.project_code current_project_code,p.name current_project_name,COALESCE(c.company_name,c.name) current_client_name,cu.name created_by_name,au.name approved_by_name,xu.name cancelled_by_name FROM client_progress_statements s JOIN client_contracts cc ON cc.id=s.client_contract_id JOIN projects p ON p.id=s.project_id JOIN clients c ON c.id=s.client_id JOIN users cu ON cu.id=s.created_by LEFT JOIN users au ON au.id=s.approved_by LEFT JOIN users xu ON xu.id=s.cancelled_by WHERE s.id=? LIMIT 1",
            [$id],
        );
    }
    public function lockStatement(int $id): ?array
    {
        return $this->one(
            "SELECT * FROM client_progress_statements WHERE id=? FOR UPDATE",
            [$id],
        );
    }
    public function create(
        string $code,
        int $contractId,
        int $projectId,
        int $clientId,
        ClientProgressStatementData $d,
        int $user,
    ): int {
        $this->db->execute(
            "INSERT INTO client_progress_statements(statement_code,statement_number,client_contract_id,project_id,client_id,statement_date,period_from,period_to,status,created_by,notes)VALUES(?,?,?,?,?,?,?,?,'draft',?,?)",
            [
                $code,
                $d->statementNumber,
                $contractId,
                $projectId,
                $clientId,
                $d->statementDate,
                $d->periodFrom,
                $d->periodTo,
                $user,
                $d->notes,
            ],
        );
        return (int) $this->db->connection()->insert_id;
    }
    public function updateDraft(int $id, ClientProgressStatementData $d): void
    {
        $this->db->execute(
            "UPDATE client_progress_statements SET statement_number=?,statement_date=?,period_from=?,period_to=?,notes=? WHERE id=? AND status='draft'",
            [
                $d->statementNumber,
                $d->statementDate,
                $d->periodFrom,
                $d->periodTo,
                $d->notes,
                $id,
            ],
        );
    }
    public function numberExists(int $c, string $n, ?int $x = null): bool
    {
        $sql =
            "SELECT 1 FROM client_progress_statements WHERE client_contract_id=? AND statement_number=?" .
            ($x === null ? "" : " AND id<>?") .
            " LIMIT 1";
        return $this->one($sql, $x === null ? [$c, $n] : [$c, $n, $x]) !== null;
    }
    public function syncDraftDetails(int $id, array $costs, array $vars): void
    {
        $this->db->execute(
            "DELETE FROM client_progress_statement_costs WHERE statement_id=? AND is_committed=0",
            [$id],
        );
        foreach (array_values($costs) as $o => $cost) {
            $this->db->execute(
                "INSERT INTO client_progress_statement_costs(statement_id,project_actual_cost_id,sort_order)VALUES(?,?,?)",
                [$id, $cost, $o],
            );
        }
        $this->db->execute(
            "DELETE FROM client_progress_statement_variations WHERE statement_id=? AND is_committed=0",
            [$id],
        );
        foreach (array_values($vars) as $o => $v) {
            $this->db->execute(
                "INSERT INTO client_progress_statement_variations(statement_id,contract_variation_id,current_billed_amount,sort_order)VALUES(?,?,?,?)",
                [$id, $v["variation_id"], $v["current_billed_amount"], $o],
            );
        }
    }
    public function eligibleCosts(int $id): array
    {
        return $this->rows(
            "SELECT x.id,x.cost_entry_code,x.cost_date,x.description,x.reference_number,x.amount,x.work_section_code_snapshot section_code,x.work_section_name_snapshot section_name,x.cost_code_snapshot cost_code,x.cost_code_name_snapshot cost_code_name,v.name vendor_name,EXISTS(SELECT 1 FROM client_progress_statement_costs own WHERE own.statement_id=s.id AND own.project_actual_cost_id=x.id) selected FROM client_progress_statements s JOIN project_actual_costs x ON x.project_id=s.project_id AND x.status='approved' AND x.cost_date<=s.statement_date LEFT JOIN vendors v ON v.id=x.vendor_id WHERE s.id=? AND NOT EXISTS(SELECT 1 FROM client_progress_statement_costs used WHERE used.project_actual_cost_id=x.id AND used.is_committed=1 AND used.statement_id<>s.id) ORDER BY x.cost_date,x.id",
            [$id],
        );
    }
    public function eligibleCostsForContract(int $id, string $date): array
    {
        return $this->rows(
            "SELECT x.id,x.cost_entry_code,x.cost_date,x.description,x.reference_number,x.amount,x.work_section_code_snapshot section_code,x.work_section_name_snapshot section_name,x.cost_code_snapshot cost_code,x.cost_code_name_snapshot cost_code_name,v.name vendor_name,0 selected FROM client_contracts c JOIN project_actual_costs x ON x.project_id=c.project_id AND x.status='approved' AND x.cost_date<=? LEFT JOIN vendors v ON v.id=x.vendor_id WHERE c.id=? AND NOT EXISTS(SELECT 1 FROM client_progress_statement_costs used WHERE used.project_actual_cost_id=x.id AND used.is_committed=1) ORDER BY x.cost_date,x.id",
            [$date, $id],
        );
    }
    public function availableVariations(int $id): array
    {
        return $this->rows(
            "SELECT v.id,v.variation_code,v.variation_number,v.title,v.amount,v.amount_effect,COALESCE(SUM(CASE WHEN old.is_committed=1 THEN old.current_billed_amount ELSE 0 END),0.00) previous_billed_amount,CAST(v.amount-COALESCE(SUM(CASE WHEN old.is_committed=1 THEN old.current_billed_amount ELSE 0 END),0) AS DECIMAL(18,2)) remaining_amount,own.current_billed_amount FROM client_progress_statements s JOIN contract_variations v ON v.client_contract_id=s.client_contract_id AND v.status='approved' AND v.amount_effect IN('increase','decrease') LEFT JOIN client_progress_statement_variations old ON old.contract_variation_id=v.id LEFT JOIN client_progress_statement_variations own ON own.statement_id=s.id AND own.contract_variation_id=v.id WHERE s.id=? GROUP BY v.id,own.current_billed_amount HAVING remaining_amount>0 OR own.current_billed_amount IS NOT NULL ORDER BY v.variation_date,v.id",
            [$id],
        );
    }
    public function availableVariationsForContract(int $id): array
    {
        return $this->rows(
            "SELECT v.id,v.variation_code,v.variation_number,v.title,v.amount,v.amount_effect,COALESCE(SUM(CASE WHEN old.is_committed=1 THEN old.current_billed_amount ELSE 0 END),0.00) previous_billed_amount,CAST(v.amount-COALESCE(SUM(CASE WHEN old.is_committed=1 THEN old.current_billed_amount ELSE 0 END),0) AS DECIMAL(18,2)) remaining_amount,NULL current_billed_amount FROM contract_variations v LEFT JOIN client_progress_statement_variations old ON old.contract_variation_id=v.id WHERE v.client_contract_id=? AND v.status='approved' AND v.amount_effect IN('increase','decrease') GROUP BY v.id HAVING remaining_amount>0 ORDER BY v.variation_date,v.id",
            [$id],
        );
    }
    public function lockCostDetails(int $id): array
    {
        return $this->rows(
            "SELECT * FROM client_progress_statement_costs WHERE statement_id=? ORDER BY project_actual_cost_id FOR UPDATE",
            [$id],
        );
    }
    public function lockSelectedCosts(int $id): array
    {
        return $this->rows(
            "SELECT x.*,v.vendor_code,v.name vendor_name FROM client_progress_statement_costs d JOIN project_actual_costs x ON x.id=d.project_actual_cost_id LEFT JOIN vendors v ON v.id=x.vendor_id WHERE d.statement_id=? ORDER BY x.id FOR UPDATE",
            [$id],
        );
    }
    public function conflictingCostIds(int $id): array
    {
        return array_map(
            "intval",
            array_column(
                $this->rows(
                    "SELECT d.project_actual_cost_id FROM client_progress_statement_costs d JOIN client_progress_statement_costs used ON used.project_actual_cost_id=d.project_actual_cost_id AND used.is_committed=1 AND used.statement_id<>d.statement_id WHERE d.statement_id=?",
                    [$id],
                ),
                "project_actual_cost_id",
            ),
        );
    }
    public function lockVariationDetails(int $id): array
    {
        return $this->rows(
            "SELECT * FROM client_progress_statement_variations WHERE statement_id=? ORDER BY contract_variation_id FOR UPDATE",
            [$id],
        );
    }
    public function lockSelectedVariations(int $id): array
    {
        return $this->rows(
            "SELECT v.*,d.current_billed_amount,COALESCE((SELECT SUM(old.current_billed_amount) FROM client_progress_statement_variations old WHERE old.contract_variation_id=v.id AND old.is_committed=1 AND old.statement_id<>d.statement_id),0.00) previous_billed_amount FROM client_progress_statement_variations d JOIN contract_variations v ON v.id=d.contract_variation_id WHERE d.statement_id=? ORDER BY v.id FOR UPDATE",
            [$id],
        );
    }
    public function latestApproved(int $c): ?array
    {
        return $this->one(
            "SELECT statement_date,statement_sequence,cumulative_cost_amount,cumulative_markup_amount,cumulative_variation_amount,cumulative_statement_amount FROM client_progress_statements WHERE client_contract_id=? AND status='approved' ORDER BY statement_sequence DESC LIMIT 1",
            [$c],
        );
    }
    public function nextSequence(int $c): int
    {
        return (int) ($this->one(
            "SELECT COALESCE(MAX(statement_sequence),0)+1 next_sequence FROM client_progress_statements WHERE client_contract_id=? AND status='approved'",
            [$c],
        )["next_sequence"] ?? 1);
    }
    public function currentCostTotal(int $id): string
    {
        return (string) ($this->one(
            "SELECT CAST(COALESCE(SUM(x.amount),0) AS DECIMAL(18,2)) total FROM client_progress_statement_costs d JOIN project_actual_costs x ON x.id=d.project_actual_cost_id WHERE d.statement_id=?",
            [$id],
        )["total"] ?? "0.00");
    }
    public function currentVariationTotal(int $id): string
    {
        return (string) ($this->one(
            "SELECT CAST(COALESCE(SUM(CASE v.amount_effect WHEN 'increase' THEN d.current_billed_amount ELSE -d.current_billed_amount END),0) AS DECIMAL(18,2)) total FROM client_progress_statement_variations d JOIN contract_variations v ON v.id=d.contract_variation_id WHERE d.statement_id=?",
            [$id],
        )["total"] ?? "0.00");
    }
    public function calculate(
        string $cost,
        string $markup,
        string $variation,
        array $p,
    ): array {
        $sql =
            "WITH a AS(SELECT CAST(? AS DECIMAL(18,2)) cost,CAST(? AS DECIMAL(7,4)) rate,CAST(? AS DECIMAL(18,2)) variation,CAST(? AS DECIMAL(18,2)) pc,CAST(? AS DECIMAL(18,2)) pm,CAST(? AS DECIMAL(18,2)) pv,CAST(? AS DECIMAL(18,2)) ps),c AS(SELECT *,ROUND(cost*rate/100,2) markup FROM a) SELECT cost current_cost_amount,markup current_markup_amount,variation current_variation_amount,CAST(cost+markup+variation AS DECIMAL(18,2)) current_statement_amount,pc previous_cost,pm previous_markup,pv previous_variation,ps previous_statement,CAST(pc+cost AS DECIMAL(18,2)) cumulative_cost_amount,CAST(pm+markup AS DECIMAL(18,2)) cumulative_markup_amount,CAST(pv+variation AS DECIMAL(18,2)) cumulative_variation_amount,CAST(ps+cost+markup+variation AS DECIMAL(18,2)) cumulative_statement_amount FROM c";
        return $this->one($sql, [
            $cost,
            $markup,
            $variation,
            $p["cost"],
            $p["markup"],
            $p["variation"],
            $p["statement"],
        ]) ?? [];
    }
    public function commitCost(int $id, array $x): void
    {
        $this->db->execute(
            "UPDATE client_progress_statement_costs SET cost_date_snapshot=?,description_snapshot=?,reference_number_snapshot=?,work_section_code_snapshot=?,work_section_name_snapshot=?,cost_code_snapshot=?,cost_code_name_snapshot=?,vendor_code_snapshot=?,vendor_name_snapshot=?,amount_snapshot=?,source_type_snapshot=?,is_committed=1 WHERE id=? AND is_committed=0",
            [
                $x["cost_date"],
                $x["description"],
                $x["reference_number"],
                $x["work_section_code_snapshot"],
                $x["work_section_name_snapshot"],
                $x["cost_code_snapshot"],
                $x["cost_code_name_snapshot"],
                $x["vendor_code"],
                $x["vendor_name"],
                $x["amount"],
                $x["source_type"],
                $id,
            ],
        );
    }
    public function commitVariation(int $id, array $v, string $prev): void
    {
        $this->db->execute(
            "UPDATE client_progress_statement_variations SET variation_code_snapshot=?,variation_number_snapshot=?,variation_title_snapshot=?,amount_effect_snapshot=?,approved_variation_amount_snapshot=?,previous_billed_amount=?,cumulative_billed_amount=CAST(? AS DECIMAL(18,2))+current_billed_amount,signed_current_amount=CASE ? WHEN 'increase' THEN current_billed_amount ELSE -current_billed_amount END,is_committed=1 WHERE id=? AND is_committed=0",
            [
                $v["variation_code"],
                $v["variation_number"],
                $v["title"],
                $v["amount_effect"],
                $v["amount"],
                $prev,
                $prev,
                $v["amount_effect"],
                $id,
            ],
        );
    }
    public function approve(
        int $id,
        int $seq,
        int $user,
        array $c,
        array $x,
    ): void {
        $this->db->execute(
            "UPDATE client_progress_statements SET statement_sequence=?,pricing_method_snapshot='cost_plus',markup_percentage_snapshot=?,client_name_snapshot=?,project_code_snapshot=?,project_name_snapshot=?,contract_code_snapshot=?,contract_number_snapshot=?,contract_title_snapshot=?,previous_cost_amount=?,current_cost_amount=?,cumulative_cost_amount=?,previous_markup_amount=?,current_markup_amount=?,cumulative_markup_amount=?,previous_variation_amount=?,current_variation_amount=?,cumulative_variation_amount=?,previous_statement_amount=?,current_statement_amount=?,cumulative_statement_amount=?,status='approved',approved_at=NOW(),approved_by=? WHERE id=? AND status='draft'",
            [
                $seq,
                $x["markup_percentage"],
                $x["client_name"],
                $x["project_code"],
                $x["project_name"],
                $x["contract_code"],
                $x["contract_number"],
                $x["title"],
                $c["previous_cost"],
                $c["current_cost_amount"],
                $c["cumulative_cost_amount"],
                $c["previous_markup"],
                $c["current_markup_amount"],
                $c["cumulative_markup_amount"],
                $c["previous_variation"],
                $c["current_variation_amount"],
                $c["cumulative_variation_amount"],
                $c["previous_statement"],
                $c["current_statement_amount"],
                $c["cumulative_statement_amount"],
                $user,
                $id,
            ],
        );
    }
    public function cancel(int $id, int $user): void
    {
        $this->db->execute(
            "UPDATE client_progress_statements SET status='cancelled',cancelled_at=NOW(),cancelled_by=? WHERE id=? AND status='draft'",
            [$user, $id],
        );
    }
    public function costs(int $id): array
    {
        return $this->rows(
            "SELECT * FROM client_progress_statement_costs WHERE statement_id=? ORDER BY sort_order,id",
            [$id],
        );
    }
    public function variations(int $id): array
    {
        return $this->rows(
            "SELECT * FROM client_progress_statement_variations WHERE statement_id=? ORDER BY sort_order,id",
            [$id],
        );
    }
    public function approvedStatementTotal(int $id): string
    {
        return (string) ($this->one(
            "SELECT current_statement_amount total FROM client_progress_statements WHERE id=? AND status='approved'",
            [$id],
        )["total"] ?? "0.00");
    }
    public function approvedClaimsTotal(int $id): string
    {
        return (string) ($this->one(
            "SELECT COALESCE(SUM(current_statement_amount),0.00) total FROM client_progress_statements WHERE client_contract_id=? AND status='approved'",
            [$id],
        )["total"] ?? "0.00");
    }
    public function references(): array
    {
        return [
            "contracts" => $this->rows(
                "SELECT id,contract_code,title FROM client_contracts WHERE pricing_method='cost_plus' ORDER BY contract_code",
            ),
            "projects" => $this->rows(
                "SELECT id,project_code,name FROM projects ORDER BY project_code",
            ),
            "clients" => $this->rows(
                "SELECT id,client_code,COALESCE(company_name,name) name FROM clients ORDER BY COALESCE(company_name,name)",
            ),
        ];
    }
    public function dataTable(ClientProgressStatementTableQuery $q): array
    {
        $w = [];
        $p = [];
        foreach (
            [
                "s.client_contract_id" => $q->contractId,
                "s.project_id" => $q->projectId,
                "s.client_id" => $q->clientId,
                "s.status" => $q->status,
            ]
            as $f => $v
        ) {
            if ($v !== null) {
                $w[] = "$f=?";
                $p[] = $v;
            }
        }
        if ($q->dateFrom) {
            $w[] = "s.statement_date>=?";
            $p[] = $q->dateFrom;
        }
        if ($q->dateTo) {
            $w[] = "s.statement_date<=?";
            $p[] = $q->dateTo;
        }
        if ($q->search !== "") {
            $x = "%" . $q->search . "%";
            $w[] =
                "(s.statement_code LIKE ? OR s.statement_number LIKE ? OR cc.contract_code LIKE ? OR p.name LIKE ? OR COALESCE(c.company_name,c.name) LIKE ?)";
            array_push($p, $x, $x, $x, $x, $x);
        }
        $where = $w ? " WHERE " . implode(" AND ", $w) : "";
        $from =
            " FROM client_progress_statements s JOIN client_contracts cc ON cc.id=s.client_contract_id JOIN projects p ON p.id=s.project_id JOIN clients c ON c.id=s.client_id LEFT JOIN users au ON au.id=s.approved_by";
        $total = $this->one(
            "SELECT COUNT(*) total FROM client_progress_statements" .
                ($q->contractId ? " WHERE client_contract_id=?" : ""),
            $q->contractId ? [$q->contractId] : [],
        );
        $filtered = $this->one("SELECT COUNT(*) total" . $from . $where, $p);
        $sort = [
            "statement_code" => "s.statement_code",
            "statement_number" => "s.statement_number",
            "statement_sequence" => "s.statement_sequence",
            "statement_date" => "s.statement_date",
            "client_name" => "client_name",
            "project_name" => "project_name",
            "contract_code" => "contract_code",
            "pricing_method" => "pricing_method",
            "current_cost_amount" => "s.current_cost_amount",
            "current_markup_amount" => "s.current_markup_amount",
            "current_variation_amount" => "s.current_variation_amount",
            "current_statement_amount" => "s.current_statement_amount",
            "cumulative_statement_amount" => "s.cumulative_statement_amount",
            "status" => "s.status",
            "approved_by_name" => "au.name",
        ];
        $sql =
            "SELECT s.id,s.statement_code,s.statement_number,s.statement_sequence,s.statement_date,CASE WHEN s.status='approved' THEN s.client_name_snapshot ELSE COALESCE(c.company_name,c.name) END client_name,CASE WHEN s.status='approved' THEN s.project_name_snapshot ELSE p.name END project_name,CASE WHEN s.status='approved' THEN s.contract_code_snapshot ELSE cc.contract_code END contract_code,COALESCE(s.pricing_method_snapshot,cc.pricing_method) pricing_method,s.current_cost_amount,s.current_markup_amount,s.current_variation_amount,s.current_statement_amount,s.cumulative_statement_amount,s.status,au.name approved_by_name" .
            $from .
            $where .
            " ORDER BY " .
            ($sort[$q->sortColumn] ?? "s.statement_date") .
            " " .
            $q->sortDirection .
            ",s.id " .
            $q->sortDirection .
            " LIMIT ? OFFSET ?";
        $r = $this->db->execute($sql, [...$p, $q->length, $q->start]);
        return [
            "recordsTotal" => (int) ($total["total"] ?? 0),
            "recordsFiltered" => (int) ($filtered["total"] ?? 0),
            "rows" =>
                $r instanceof \mysqli_result ? $r->fetch_all(MYSQLI_ASSOC) : [],
        ];
    }
    public function decimalCompare(string $a, string $b): int
    {
        return (int) ($this->one(
            "SELECT CASE WHEN CAST(? AS DECIMAL(18,2))<CAST(? AS DECIMAL(18,2)) THEN -1 WHEN CAST(? AS DECIMAL(18,2))>CAST(? AS DECIMAL(18,2)) THEN 1 ELSE 0 END comparison",
            [$a, $b, $a, $b],
        )["comparison"] ?? 0);
    }
    public function decimalAdd(string $a, string $b): string
    {
        return (string) ($this->one(
            "SELECT CAST(CAST(? AS DECIMAL(18,2))+CAST(? AS DECIMAL(18,2)) AS DECIMAL(18,2)) total",
            [$a, $b],
        )["total"] ?? "0.00");
    }
    private function one(string $sql, array $params = []): ?array
    {
        $r = $this->db->execute($sql, $params);
        $x = $r instanceof \mysqli_result ? $r->fetch_assoc() : null;
        return is_array($x) ? $x : null;
    }
    private function rows(string $sql, array $params = []): array
    {
        $r = $this->db->execute($sql, $params);
        return $r instanceof \mysqli_result ? $r->fetch_all(MYSQLI_ASSOC) : [];
    }
}
