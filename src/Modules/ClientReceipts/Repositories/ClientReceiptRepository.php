<?php

declare(strict_types=1);

namespace App\Modules\ClientReceipts\Repositories;

use App\Core\Database\Database;
use App\Modules\ClientReceipts\DTOs\ClientReceiptData;
use App\Modules\ClientReceipts\DTOs\ClientReceiptTableQuery;

final class ClientReceiptRepository
{
    public function __construct(private readonly Database $db) {}

    public function contract(int $id, bool $lock = false): ?array
    {
        if (
            $lock &&
            $this->one(
                "SELECT id FROM client_contracts WHERE id=? FOR UPDATE",
                [$id],
            ) === null
        ) {
            return null;
        }
        return $this->one(
            "SELECT c.*,p.project_code,p.name project_name,cl.name client_name FROM client_contracts c JOIN projects p ON p.id=c.project_id JOIN clients cl ON cl.id=c.client_id WHERE c.id=? LIMIT 1",
            [$id],
        );
    }

    public function find(int $id, bool $lock = false): ?array
    {
        if (
            $lock &&
            $this->one("SELECT id FROM client_receipts WHERE id=? FOR UPDATE", [
                $id,
            ]) === null
        ) {
            return null;
        }
        return $this->one(
            "SELECT r.*,c.contract_code,c.contract_number,c.title contract_title,c.status contract_status,p.project_code,p.name project_name,cl.name client_name,cu.name created_by_name,pu.name posted_by_name,xu.name cancelled_by_name,COALESCE(a.allocated,0.00) allocated_amount,CASE WHEN r.status='posted' THEN r.amount-COALESCE(a.allocated,0.00) ELSE NULL END unallocated_amount FROM client_receipts r JOIN client_contracts c ON c.id=r.client_contract_id JOIN projects p ON p.id=r.project_id JOIN clients cl ON cl.id=r.client_id JOIN users cu ON cu.id=r.created_by LEFT JOIN users pu ON pu.id=r.posted_by LEFT JOIN users xu ON xu.id=r.cancelled_by LEFT JOIN(SELECT receipt_id,SUM(allocated_amount) allocated FROM client_receipt_allocations GROUP BY receipt_id)a ON a.receipt_id=r.id WHERE r.id=? LIMIT 1",
            [$id],
        );
    }

    public function create(
        string $code,
        ClientReceiptData $data,
        array $contract,
        int $userId,
    ): int {
        $this->db->execute(
            "INSERT INTO client_receipts(receipt_code,receipt_number,client_contract_id,project_id,client_id,receipt_date,amount,payment_method,reference_number,status,created_by,notes)VALUES(?,?,?,?,?,?,?,?,?,'draft',?,?)",
            [
                $code,
                $data->receiptNumber,
                $data->contractId,
                (int) $contract["project_id"],
                (int) $contract["client_id"],
                $data->receiptDate,
                $data->amount,
                $data->paymentMethod,
                $data->referenceNumber,
                $userId,
                $data->notes,
            ],
        );
        return (int) $this->db->connection()->insert_id;
    }

    public function update(int $id, ClientReceiptData $data): void
    {
        $this->db->execute(
            "UPDATE client_receipts SET receipt_number=?,receipt_date=?,amount=?,payment_method=?,reference_number=?,notes=? WHERE id=? AND status='draft'",
            [
                $data->receiptNumber,
                $data->receiptDate,
                $data->amount,
                $data->paymentMethod,
                $data->referenceNumber,
                $data->notes,
                $id,
            ],
        );
    }

    public function receiptNumberExists(
        int $contractId,
        string $number,
        ?int $exceptId = null,
    ): bool {
        $sql =
            "SELECT id FROM client_receipts WHERE client_contract_id=? AND receipt_number=?";
        $params = [$contractId, $number];
        if ($exceptId !== null) {
            $sql .= " AND id<>?";
            $params[] = $exceptId;
        }
        return $this->one($sql . " LIMIT 1", $params) !== null;
    }

    public function amountIsPositive(string $amount): bool
    {
        return (int) ($this->one("SELECT CAST(? AS DECIMAL(18,2))>0 positive", [
            $amount,
        ])["positive"] ?? 0) === 1;
    }

    public function post(int $id, int $userId, array $contract): void
    {
        $this->db->execute(
            "UPDATE client_receipts SET client_name_snapshot=?,project_code_snapshot=?,project_name_snapshot=?,contract_code_snapshot=?,contract_number_snapshot=?,contract_title_snapshot=?,status='posted',posted_at=NOW(),posted_by=? WHERE id=? AND status='draft'",
            [
                $contract["client_name"],
                $contract["project_code"],
                $contract["project_name"],
                $contract["contract_code"],
                $contract["contract_number"],
                $contract["title"],
                $userId,
                $id,
            ],
        );
    }

    public function cancel(int $id, int $userId): void
    {
        $this->db->execute(
            "UPDATE client_receipts SET status='cancelled',cancelled_at=NOW(),cancelled_by=? WHERE id=? AND status='draft'",
            [$userId, $id],
        );
    }

    /** @param list<int> $ids @return array<int,array> */
    public function lockStatements(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $placeholders = implode(",", array_fill(0, count($ids), "?"));
        $this->db->execute(
            "SELECT id FROM client_progress_statements WHERE id IN($placeholders) ORDER BY id FOR UPDATE",
            $ids,
        );
        $result = $this->db->execute(
            "SELECT s.*,COALESCE(a.allocated,0.00) allocated_amount,s.current_statement_amount-COALESCE(a.allocated,0.00) outstanding_amount FROM client_progress_statements s LEFT JOIN(SELECT client_progress_statement_id,SUM(allocated_amount) allocated FROM client_receipt_allocations GROUP BY client_progress_statement_id)a ON a.client_progress_statement_id=s.id WHERE s.id IN($placeholders) ORDER BY s.id",
            $ids,
        );
        $rows =
            $result instanceof \mysqli_result
                ? $result->fetch_all(MYSQLI_ASSOC)
                : [];
        $indexed = [];
        foreach ($rows as $row) {
            $indexed[(int) $row["id"]] = $row;
        }
        return $indexed;
    }

    public function receiptBalance(int $receiptId): array
    {
        return $this->one(
            "SELECT r.amount,COALESCE(SUM(a.allocated_amount),0.00) allocated,r.amount-COALESCE(SUM(a.allocated_amount),0.00) unallocated,(COALESCE(SUM(a.allocated_amount),0.00)>r.amount) inconsistent FROM client_receipts r LEFT JOIN client_receipt_allocations a ON a.receipt_id=r.id WHERE r.id=? GROUP BY r.id,r.amount",
            [$receiptId],
        ) ?? [
            "amount" => "0.00",
            "allocated" => "0.00",
            "unallocated" => "0.00",
            "inconsistent" => 1,
        ];
    }

    public function statementBalance(int $statementId): array
    {
        return $this->one(
            "SELECT s.current_statement_amount amount,COALESCE(SUM(a.allocated_amount),0.00) allocated,s.current_statement_amount-COALESCE(SUM(a.allocated_amount),0.00) outstanding,(COALESCE(SUM(a.allocated_amount),0.00)>s.current_statement_amount) inconsistent FROM client_progress_statements s LEFT JOIN client_receipt_allocations a ON a.client_progress_statement_id=s.id WHERE s.id=? GROUP BY s.id,s.current_statement_amount",
            [$statementId],
        ) ?? [
            "amount" => "0.00",
            "allocated" => "0.00",
            "outstanding" => "0.00",
            "inconsistent" => 1,
        ];
    }

    /** @param list<string> $amounts */
    public function sumDecimals(array $amounts): string
    {
        $parts = array_fill(0, count($amounts), "CAST(? AS DECIMAL(18,2))");
        return (string) ($this->one(
            "SELECT " . implode("+", $parts) . " total",
            $amounts,
        )["total"] ?? "0.00");
    }

    public function exceeds(string $left, string $right): bool
    {
        return (int) ($this->one(
            "SELECT CAST(? AS DECIMAL(18,2))>CAST(? AS DECIMAL(18,2)) exceeds",
            [$left, $right],
        )["exceeds"] ?? 1) === 1;
    }

    public function insertAllocation(
        int $receiptId,
        array $statement,
        string $amount,
        ?string $notes,
        int $userId,
    ): void {
        $this->db->execute(
            "INSERT INTO client_receipt_allocations(receipt_id,client_progress_statement_id,allocated_amount,statement_code_snapshot,statement_number_snapshot,statement_sequence_snapshot,statement_date_snapshot,statement_amount_snapshot,allocated_at,allocated_by,notes)VALUES(?,?,?,?,?,?,?,?,NOW(),?,?)",
            [
                $receiptId,
                (int) $statement["id"],
                $amount,
                $statement["statement_code"],
                $statement["statement_number"],
                (int) $statement["statement_sequence"],
                $statement["statement_date"],
                $statement["current_statement_amount"],
                $userId,
                $notes,
            ],
        );
    }

    public function allocations(int $receiptId): array
    {
        $result = $this->db->execute(
            "SELECT a.*,u.name allocated_by_name FROM client_receipt_allocations a JOIN users u ON u.id=a.allocated_by WHERE a.receipt_id=? ORDER BY a.allocated_at,a.id",
            [$receiptId],
        );
        return $result instanceof \mysqli_result
            ? $result->fetch_all(MYSQLI_ASSOC)
            : [];
    }

    public function eligibleStatements(int $contractId): array
    {
        $result = $this->db->execute(
            "SELECT s.id,s.statement_code,s.statement_number,s.statement_sequence,s.statement_date,s.current_statement_amount,COALESCE(a.allocated,0.00) allocated_amount,s.current_statement_amount-COALESCE(a.allocated,0.00) outstanding_amount FROM client_progress_statements s LEFT JOIN(SELECT client_progress_statement_id,SUM(allocated_amount) allocated FROM client_receipt_allocations GROUP BY client_progress_statement_id)a ON a.client_progress_statement_id=s.id WHERE s.client_contract_id=? AND s.status='approved' AND s.current_statement_amount>COALESCE(a.allocated,0.00) ORDER BY s.statement_date,s.statement_sequence,s.id",
            [$contractId],
        );
        return $result instanceof \mysqli_result
            ? $result->fetch_all(MYSQLI_ASSOC)
            : [];
    }

    public function financialSummary(int $contractId): array
    {
        return $this->one(
            "SELECT COALESCE((SELECT SUM(s.current_statement_amount) FROM client_progress_statements s WHERE s.client_contract_id=? AND s.status='approved'),0.00) approved_claims,COALESCE((SELECT SUM(r.amount) FROM client_receipts r WHERE r.client_contract_id=? AND r.status='posted'),0.00) posted_receipts,COALESCE((SELECT SUM(a.allocated_amount) FROM client_receipt_allocations a JOIN client_receipts r ON r.id=a.receipt_id JOIN client_progress_statements s ON s.id=a.client_progress_statement_id WHERE r.client_contract_id=? AND s.client_contract_id=?),0.00) allocated_receipts,EXISTS(SELECT 1 FROM client_receipt_allocations a JOIN client_receipts r ON r.id=a.receipt_id JOIN client_progress_statements s ON s.id=a.client_progress_statement_id WHERE (r.client_contract_id=? OR s.client_contract_id=?) AND r.client_contract_id<>s.client_contract_id) cross_contract,EXISTS(SELECT 1 FROM client_receipts r JOIN client_receipt_allocations a ON a.receipt_id=r.id WHERE r.client_contract_id=? GROUP BY r.id,r.amount HAVING SUM(a.allocated_amount)>r.amount) receipt_overallocated,EXISTS(SELECT 1 FROM client_progress_statements s JOIN client_receipt_allocations a ON a.client_progress_statement_id=s.id WHERE s.client_contract_id=? GROUP BY s.id,s.current_statement_amount HAVING SUM(a.allocated_amount)>s.current_statement_amount) statement_overallocated,EXISTS(SELECT 1 FROM client_receipt_allocations a JOIN client_receipts r ON r.id=a.receipt_id JOIN client_progress_statements s ON s.id=a.client_progress_statement_id WHERE (r.client_contract_id=? OR s.client_contract_id=?) AND (r.status<>'posted' OR s.status<>'approved')) invalid_status",
            [
                $contractId,
                $contractId,
                $contractId,
                $contractId,
                $contractId,
                $contractId,
                $contractId,
                $contractId,
                $contractId,
                $contractId,
            ],
        ) ?? [];
    }

    public function calculateSummary(
        string $claims,
        string $receipts,
        string $allocated,
    ): array {
        return $this->one(
            "SELECT CAST(? AS DECIMAL(18,2))-CAST(? AS DECIMAL(18,2)) statement_outstanding,CAST(? AS DECIMAL(18,2))-CAST(? AS DECIMAL(18,2)) unallocated_receipts,(CAST(? AS DECIMAL(18,2))>CAST(? AS DECIMAL(18,2)) OR CAST(? AS DECIMAL(18,2))>CAST(? AS DECIMAL(18,2))) inconsistent",
            [
                $claims,
                $allocated,
                $receipts,
                $allocated,
                $allocated,
                $claims,
                $allocated,
                $receipts,
            ],
        ) ?? [
            "statement_outstanding" => "0.00",
            "unallocated_receipts" => "0.00",
            "inconsistent" => 1,
        ];
    }

    public function references(): array
    {
        $clients = $this->db->execute(
            "SELECT id,client_code,name FROM clients ORDER BY name",
        );
        $projects = $this->db->execute(
            "SELECT id,project_code,name FROM projects ORDER BY name",
        );
        $contracts = $this->db->execute(
            "SELECT id,contract_code,contract_number,title FROM client_contracts ORDER BY contract_date DESC,id DESC",
        );
        return [
            "clients" =>
                $clients instanceof \mysqli_result
                    ? $clients->fetch_all(MYSQLI_ASSOC)
                    : [],
            "projects" =>
                $projects instanceof \mysqli_result
                    ? $projects->fetch_all(MYSQLI_ASSOC)
                    : [],
            "contracts" =>
                $contracts instanceof \mysqli_result
                    ? $contracts->fetch_all(MYSQLI_ASSOC)
                    : [],
        ];
    }

    public function dataTable(ClientReceiptTableQuery $query): array
    {
        $where = ["1=1"];
        $params = [];
        foreach (
            [
                "r.client_contract_id" => $query->contractId,
                "r.project_id" => $query->projectId,
                "r.client_id" => $query->clientId,
                "r.status" => $query->status,
                "r.payment_method" => $query->paymentMethod,
            ]
            as $field => $value
        ) {
            if ($value !== null) {
                $where[] = "$field=?";
                $params[] = $value;
            }
        }
        if ($query->dateFrom !== null) {
            $where[] = "r.receipt_date>=?";
            $params[] = $query->dateFrom;
        }
        if ($query->dateTo !== null) {
            $where[] = "r.receipt_date<=?";
            $params[] = $query->dateTo;
        }
        if ($query->search !== "") {
            $search = "%" . $query->search . "%";
            $where[] =
                "(r.receipt_code LIKE ? OR r.receipt_number LIKE ? OR cl.name LIKE ? OR p.name LIKE ? OR c.contract_code LIKE ? OR r.reference_number LIKE ?)";
            array_push(
                $params,
                $search,
                $search,
                $search,
                $search,
                $search,
                $search,
            );
        }
        $clause = " WHERE " . implode(" AND ", $where);
        $totalParams = [];
        $totalWhere = "";
        if ($query->contractId !== null) {
            $totalWhere = " WHERE client_contract_id=?";
            $totalParams[] = $query->contractId;
        }
        $total = $this->one(
            "SELECT COUNT(*) total FROM client_receipts" . $totalWhere,
            $totalParams,
        );
        $filtered = $this->one(
            "SELECT COUNT(*) total FROM client_receipts r JOIN clients cl ON cl.id=r.client_id JOIN projects p ON p.id=r.project_id JOIN client_contracts c ON c.id=r.client_contract_id" .
                $clause,
            $params,
        );
        $sort = [
            "receipt_code" => "r.receipt_code",
            "receipt_number" => "r.receipt_number",
            "receipt_date" => "r.receipt_date",
            "client_name" => "cl.name",
            "project_name" => "p.name",
            "contract_code" => "c.contract_code",
            "amount" => "r.amount",
            "allocated" => "allocated_amount",
            "unallocated" => "unallocated_amount",
            "payment_method" => "r.payment_method",
            "reference_number" => "r.reference_number",
            "status" => "r.status",
            "posted_by_name" => "pu.name",
        ];
        $result = $this->db->execute(
            "SELECT r.id,r.receipt_code,r.receipt_number,r.receipt_date,r.amount,r.payment_method,r.reference_number,r.status,CASE WHEN r.status='posted' THEN COALESCE(a.allocated,0.00) ELSE NULL END allocated_amount,CASE WHEN r.status='posted' THEN r.amount-COALESCE(a.allocated,0.00) ELSE NULL END unallocated_amount,cl.name client_name,p.name project_name,c.contract_code,c.contract_number,pu.name posted_by_name FROM client_receipts r JOIN clients cl ON cl.id=r.client_id JOIN projects p ON p.id=r.project_id JOIN client_contracts c ON c.id=r.client_contract_id LEFT JOIN users pu ON pu.id=r.posted_by LEFT JOIN(SELECT receipt_id,SUM(allocated_amount) allocated FROM client_receipt_allocations GROUP BY receipt_id)a ON a.receipt_id=r.id" .
                $clause .
                " ORDER BY " .
                ($sort[$query->sortColumn] ?? "r.receipt_date") .
                " " .
                $query->sortDirection .
                ",r.id " .
                $query->sortDirection .
                " LIMIT ? OFFSET ?",
            [...$params, $query->length, $query->start],
        );
        return [
            "recordsTotal" => (int) ($total["total"] ?? 0),
            "recordsFiltered" => (int) ($filtered["total"] ?? 0),
            "rows" =>
                $result instanceof \mysqli_result
                    ? $result->fetch_all(MYSQLI_ASSOC)
                    : [],
        ];
    }

    private function one(string $sql, array $params = []): ?array
    {
        $result = $this->db->execute($sql, $params);
        $row =
            $result instanceof \mysqli_result ? $result->fetch_assoc() : null;
        return is_array($row) ? $row : null;
    }
}
