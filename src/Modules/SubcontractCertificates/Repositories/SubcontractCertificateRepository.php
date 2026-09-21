<?php

declare(strict_types=1);

namespace App\Modules\SubcontractCertificates\Repositories;

use App\Core\Database\Database;
use App\Modules\SubcontractCertificates\DTOs\SubcontractCertificateData;
use App\Modules\SubcontractCertificates\DTOs\SubcontractCertificateTableQuery;

final class SubcontractCertificateRepository
{
    public function __construct(private readonly Database $db) {}

    public function subcontract(int $id, bool $lock = false): ?array
    {
        if ($lock && $this->one('SELECT id FROM subcontracts WHERE id=? FOR UPDATE', [$id]) === null) return null;
        return $this->one("SELECT s.*,p.project_code,p.name project_name,v.vendor_code,v.name vendor_name,b.id boq_id,b.status boq_status FROM subcontracts s JOIN projects p ON p.id=s.project_id JOIN vendors v ON v.id=s.vendor_id LEFT JOIN subcontract_boqs b ON b.subcontract_id=s.id WHERE s.id=? LIMIT 1", [$id]);
    }

    public function find(int $id, bool $lock = false): ?array
    {
        if ($lock && $this->one('SELECT id FROM subcontract_progress_certificates WHERE id=? FOR UPDATE', [$id]) === null) return null;
        return $this->one("SELECT c.*,s.subcontract_code,s.subcontract_number,s.title subcontract_title,s.pricing_method,s.contract_value,s.status subcontract_status,p.name project_name,v.name vendor_name,au.name approved_by_name,cu.name cancelled_by_name,CASE WHEN s.pricing_method='boq' AND s.contract_value>0 THEN ROUND(COALESCE(c.current_earned_value,0)*100/s.contract_value,4) WHEN s.pricing_method='boq' THEN 0 ELSE c.current_progress_percentage END display_current_progress_percentage,CASE WHEN s.pricing_method='boq' AND s.contract_value>0 THEN ROUND(COALESCE(c.cumulative_earned_value,0)*100/s.contract_value,4) WHEN s.pricing_method='boq' THEN 0 ELSE c.cumulative_progress_percentage END display_cumulative_progress_percentage FROM subcontract_progress_certificates c JOIN subcontracts s ON s.id=c.subcontract_id JOIN projects p ON p.id=s.project_id JOIN vendors v ON v.id=s.vendor_id LEFT JOIN users au ON au.id=c.approved_by LEFT JOIN users cu ON cu.id=c.cancelled_by WHERE c.id=? LIMIT 1", [$id]);
    }

    public function numberExists(int $subcontractId, string $number, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM subcontract_progress_certificates WHERE subcontract_id=? AND certificate_number=?' . ($exceptId === null ? '' : ' AND id<>?') . ' LIMIT 1';
        $params = $exceptId === null ? [$subcontractId, $number] : [$subcontractId, $number, $exceptId];
        $result = $this->db->execute($sql, $params);
        return $result instanceof \mysqli_result && $result->num_rows > 0;
    }

    public function create(string $code, SubcontractCertificateData $data): int
    {
        $this->db->execute("INSERT INTO subcontract_progress_certificates(certificate_code,certificate_number,subcontract_id,period_from,period_to,certificate_date,status,current_progress_percentage,notes) VALUES(?,?,?,?,?,?,'draft',?,?)", [$code, $data->certificateNumber, $data->subcontractId, $data->periodFrom, $data->periodTo, $data->certificateDate, $data->currentProgressPercentage, $data->notes]);
        return (int) $this->db->connection()->insert_id;
    }

    public function seedBoqItems(int $certificateId, int $boqId): void
    {
        $this->db->execute("INSERT INTO subcontract_progress_items(certificate_id,subcontract_boq_item_id,description_snapshot,pricing_type_snapshot,unit_name_snapshot,unit_symbol_snapshot,contract_quantity,unit_rate,lump_sum_amount_snapshot,previous_quantity,current_quantity,cumulative_quantity,previous_progress_percentage,current_progress_percentage,cumulative_progress_percentage,sort_order) SELECT ?,i.id,i.description,i.pricing_type,i.unit_name,i.unit_symbol,i.quantity,i.unit_rate,i.lump_sum_amount,IF(i.pricing_type='quantity',0,NULL),IF(i.pricing_type='quantity',0,NULL),IF(i.pricing_type='quantity',0,NULL),IF(i.pricing_type='lump_sum',0,NULL),IF(i.pricing_type='lump_sum',0,NULL),IF(i.pricing_type='lump_sum',0,NULL),(s.sort_order*100000+i.sort_order) FROM subcontract_boq_items i JOIN subcontract_boq_sections s ON s.id=i.subcontract_boq_section_id WHERE s.subcontract_boq_id=? ORDER BY s.sort_order,s.id,i.sort_order,i.id", [$certificateId, $boqId]);
    }

    public function boqSourceItems(int $boqId, int $subcontractId): array
    {
        $result = $this->db->execute("SELECT i.id subcontract_boq_item_id,i.description description_snapshot,i.pricing_type pricing_type_snapshot,i.unit_name unit_name_snapshot,i.unit_symbol unit_symbol_snapshot,i.quantity contract_quantity,i.unit_rate,i.lump_sum_amount lump_sum_amount_snapshot,(s.sort_order*100000+i.sort_order) sort_order,COALESCE(h.previous_measure,0) live_previous_quantity,COALESCE(h.previous_measure,0) live_cumulative_quantity,0.0000 current_quantity,0.00 live_current_amount,CASE WHEN i.pricing_type='lump_sum' THEN ROUND(COALESCE(h.previous_measure,0)*i.lump_sum_amount/100,2) ELSE ROUND(COALESCE(h.previous_measure,0)*i.unit_rate,2) END live_cumulative_amount FROM subcontract_boq_items i JOIN subcontract_boq_sections s ON s.id=i.subcontract_boq_section_id LEFT JOIN(SELECT pi.subcontract_boq_item_id,SUM(CASE WHEN pi.pricing_type_snapshot='lump_sum' THEN pi.current_progress_percentage ELSE pi.current_quantity END) previous_measure FROM subcontract_progress_items pi JOIN subcontract_progress_certificates pc ON pc.id=pi.certificate_id WHERE pc.subcontract_id=? AND pc.status='approved' GROUP BY pi.subcontract_boq_item_id)h ON h.subcontract_boq_item_id=i.id WHERE s.subcontract_boq_id=? ORDER BY s.sort_order,s.id,i.sort_order,i.id", [$subcontractId, $boqId]);
        return $result instanceof \mysqli_result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function updateDraft(int $id, SubcontractCertificateData $data): void
    {
        $this->db->execute('UPDATE subcontract_progress_certificates SET certificate_number=?,period_from=?,period_to=?,certificate_date=?,current_progress_percentage=?,notes=? WHERE id=? AND status=\'draft\'', [$data->certificateNumber, $data->periodFrom, $data->periodTo, $data->certificateDate, $data->currentProgressPercentage, $data->notes, $id]);
    }

    /** @param array<int, string> $quantities */
    public function updateQuantities(int $certificateId, array $quantities): void
    {
        foreach ($quantities as $boqItemId => $quantity) {
            $this->db->execute("UPDATE subcontract_progress_items SET current_quantity=IF(pricing_type_snapshot='quantity',?,NULL),current_progress_percentage=IF(pricing_type_snapshot='lump_sum',?,NULL) WHERE certificate_id=? AND subcontract_boq_item_id=?", [$quantity, $quantity, $certificateId, $boqItemId]);
        }
    }

    public function itemCount(int $certificateId): int
    {
        $row = $this->one('SELECT COUNT(*) total FROM subcontract_progress_items WHERE certificate_id=?', [$certificateId]);
        return (int) ($row['total'] ?? 0);
    }

    public function submittedItemCount(int $certificateId, array $itemIds): int
    {
        if ($itemIds === []) return 0;
        $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
        $row = $this->one("SELECT COUNT(*) total FROM subcontract_progress_items WHERE certificate_id=? AND subcontract_boq_item_id IN($placeholders)", [$certificateId, ...$itemIds]);
        return (int) ($row['total'] ?? 0);
    }

    public function items(int $certificateId): array
    {
        $result = $this->db->execute("SELECT i.*,COALESCE(h.previous_measure,0) live_previous_quantity,COALESCE(h.previous_measure,0)+CASE WHEN i.pricing_type_snapshot='lump_sum' THEN i.current_progress_percentage ELSE i.current_quantity END live_cumulative_quantity,CASE WHEN i.pricing_type_snapshot='lump_sum' THEN ROUND(i.current_progress_percentage*i.lump_sum_amount_snapshot/100,2) ELSE ROUND(i.current_quantity*i.unit_rate,2) END live_current_amount,CASE WHEN i.pricing_type_snapshot='lump_sum' THEN ROUND((COALESCE(h.previous_measure,0)+i.current_progress_percentage)*i.lump_sum_amount_snapshot/100,2) ELSE ROUND((COALESCE(h.previous_measure,0)+i.current_quantity)*i.unit_rate,2) END live_cumulative_amount FROM subcontract_progress_items i LEFT JOIN(SELECT pi.subcontract_boq_item_id,SUM(CASE WHEN pi.pricing_type_snapshot='lump_sum' THEN pi.current_progress_percentage ELSE pi.current_quantity END) previous_measure FROM subcontract_progress_items pi JOIN subcontract_progress_certificates pc ON pc.id=pi.certificate_id WHERE pc.status='approved' AND pc.id<>? GROUP BY pi.subcontract_boq_item_id)h ON h.subcontract_boq_item_id=i.subcontract_boq_item_id WHERE i.certificate_id=? ORDER BY i.sort_order,i.id", [$certificateId, $certificateId]);
        return $result instanceof \mysqli_result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /** @return array<int, string> */
    public function approvedQuantities(int $subcontractId): array
    {
        $result = $this->db->execute("SELECT pi.subcontract_boq_item_id,SUM(CASE WHEN pi.pricing_type_snapshot='lump_sum' THEN pi.current_progress_percentage ELSE pi.current_quantity END) quantity FROM subcontract_progress_items pi JOIN subcontract_progress_certificates c ON c.id=pi.certificate_id WHERE c.subcontract_id=? AND c.status='approved' GROUP BY pi.subcontract_boq_item_id", [$subcontractId]);
        $rows = $result instanceof \mysqli_result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $map = [];
        foreach ($rows as $row) $map[(int) $row['subcontract_boq_item_id']] = (string) $row['quantity'];
        return $map;
    }

    public function finalizeBoqItem(int $certificateId, int $boqItemId, string $previous): void
    {
        $this->db->execute("UPDATE subcontract_progress_items SET previous_quantity=IF(pricing_type_snapshot='quantity',?,NULL),cumulative_quantity=IF(pricing_type_snapshot='quantity',?+current_quantity,NULL),previous_progress_percentage=IF(pricing_type_snapshot='lump_sum',?,NULL),cumulative_progress_percentage=IF(pricing_type_snapshot='lump_sum',?+current_progress_percentage,NULL),current_amount=CASE WHEN pricing_type_snapshot='lump_sum' THEN ROUND(current_progress_percentage*lump_sum_amount_snapshot/100,2) ELSE ROUND(current_quantity*unit_rate,2) END,cumulative_amount=CASE WHEN pricing_type_snapshot='lump_sum' THEN ROUND((?+current_progress_percentage)*lump_sum_amount_snapshot/100,2) ELSE ROUND((?+current_quantity)*unit_rate,2) END WHERE certificate_id=? AND subcontract_boq_item_id=?", [$previous,$previous,$previous,$previous,$previous,$previous,$certificateId,$boqItemId]);
    }

    public function invalidBoqItemCount(int $certificateId): int
    {
        $row = $this->one("SELECT COUNT(*) total FROM subcontract_progress_items WHERE certificate_id=? AND ((pricing_type_snapshot='quantity' AND cumulative_quantity>contract_quantity) OR (pricing_type_snapshot='lump_sum' AND cumulative_progress_percentage>100))", [$certificateId]);
        return (int) ($row['total'] ?? 0);
    }

    public function positiveBoqItemCount(int $certificateId): int
    {
        $row = $this->one("SELECT COUNT(*) total FROM subcontract_progress_items WHERE certificate_id=? AND ((pricing_type_snapshot='quantity' AND current_quantity>0) OR (pricing_type_snapshot='lump_sum' AND current_progress_percentage>0))", [$certificateId]);
        return (int) ($row['total'] ?? 0);
    }

    public function boqTotals(int $certificateId): array
    {
        return $this->one('SELECT COALESCE(SUM(cumulative_amount-current_amount),0) previous_value,COALESCE(SUM(current_amount),0) current_value,COALESCE(SUM(cumulative_amount),0) cumulative_value FROM subcontract_progress_items WHERE certificate_id=?', [$certificateId]) ?? ['previous_value' => '0.00', 'current_value' => '0.00', 'cumulative_value' => '0.00'];
    }

    public function latestApprovedSequence(int $subcontractId): ?array
    {
        return $this->one("SELECT id,certificate_date FROM subcontract_progress_certificates WHERE subcontract_id=? AND status='approved' ORDER BY certificate_date DESC,id DESC LIMIT 1", [$subcontractId]);
    }

    public function approvedLumpProgress(int $subcontractId): string
    {
        $row = $this->one("SELECT COALESCE(SUM(current_progress_percentage),0) progress FROM subcontract_progress_certificates WHERE subcontract_id=? AND status='approved'", [$subcontractId]);
        return (string) ($row['progress'] ?? '0.0000');
    }

    public function lumpProgressExceeds(string $previous, string $current): bool
    {
        $row = $this->one('SELECT (?+?)>100 exceeds', [$previous, $current]);
        return (int) ($row['exceeds'] ?? 1) === 1;
    }

    public function lumpProgressIsPositive(string $current): bool
    {
        $row = $this->one('SELECT ?>0 positive', [$current]);
        return (int) ($row['positive'] ?? 0) === 1;
    }

    public function approveBoq(int $id, int $userId, array $totals): void
    {
        $this->db->execute("UPDATE subcontract_progress_certificates SET previous_progress_percentage=NULL,current_progress_percentage=NULL,cumulative_progress_percentage=NULL,previous_earned_value=?,current_earned_value=?,cumulative_earned_value=?,status='approved',approved_at=NOW(),approved_by=? WHERE id=? AND status='draft'", [(string) $totals['previous_value'], (string) $totals['current_value'], (string) $totals['cumulative_value'], $userId, $id]);
    }

    public function approveLump(int $id, int $userId, string $previous, string $contractValue): void
    {
        $this->db->execute("UPDATE subcontract_progress_certificates SET previous_progress_percentage=?,cumulative_progress_percentage=?+current_progress_percentage,previous_earned_value=ROUND(?*?/100,2),current_earned_value=ROUND(?*current_progress_percentage/100,2),cumulative_earned_value=ROUND(?*(?+current_progress_percentage)/100,2),status='approved',approved_at=NOW(),approved_by=? WHERE id=? AND status='draft'", [$previous, $previous, $contractValue, $previous, $contractValue, $contractValue, $previous, $userId, $id]);
    }

    public function cancel(int $id, int $userId): void
    {
        $this->db->execute("UPDATE subcontract_progress_certificates SET status='cancelled',cancelled_at=NOW(),cancelled_by=? WHERE id=? AND status='draft'", [$userId, $id]);
    }

    public function approvedEarnedValue(int $subcontractId): string
    {
        $row = $this->one("SELECT cumulative_earned_value FROM subcontract_progress_certificates WHERE subcontract_id=? AND status='approved' ORDER BY certificate_date DESC,id DESC LIMIT 1", [$subcontractId]);
        return (string) ($row['cumulative_earned_value'] ?? '0.00');
    }

    public function approvedProgressPercentage(int $subcontractId): string
    {
        $row = $this->one("SELECT CASE WHEN s.pricing_method='boq' THEN CASE WHEN s.contract_value>0 THEN ROUND(COALESCE(c.cumulative_earned_value,0)*100/s.contract_value,4) ELSE 0 END ELSE COALESCE(c.cumulative_progress_percentage,0) END progress FROM subcontracts s LEFT JOIN subcontract_progress_certificates c ON c.id=(SELECT c2.id FROM subcontract_progress_certificates c2 WHERE c2.subcontract_id=s.id AND c2.status='approved' ORDER BY c2.certificate_date DESC,c2.id DESC LIMIT 1) WHERE s.id=?", [$subcontractId]);
        return (string) ($row['progress'] ?? '0.0000');
    }

    public function dataTable(SubcontractCertificateTableQuery $query): array
    {
        $where = ['c.subcontract_id=?'];
        $params = [$query->subcontractId];
        if ($query->search !== '') {
            $search = '%' . $query->search . '%';
            $where[] = '(c.certificate_code LIKE ? OR c.certificate_number LIKE ? OR DATE_FORMAT(c.certificate_date,\'%Y-%m-%d\') LIKE ? OR c.status LIKE ?)';
            array_push($params, $search, $search, $search, $search);
        }
        if ($query->status !== null) {
            $where[] = 'c.status=?';
            $params[] = $query->status;
        }
        $clause = ' WHERE ' . implode(' AND ', $where);
        $total = $this->one('SELECT COUNT(*) total FROM subcontract_progress_certificates WHERE subcontract_id=?', [$query->subcontractId]);
        $filtered = $this->one('SELECT COUNT(*) total FROM subcontract_progress_certificates c' . $clause, $params);
        $sort = ['certificate_code' => 'c.certificate_code', 'certificate_number' => 'c.certificate_number', 'certificate_date' => 'c.certificate_date', 'period_from' => 'c.period_from', 'previous_earned_value' => 'c.previous_earned_value', 'current_earned_value' => 'c.current_earned_value', 'cumulative_earned_value' => 'c.cumulative_earned_value', 'status' => 'c.status'];
        $result = $this->db->execute('SELECT c.id,c.certificate_code,c.certificate_number,c.certificate_date,c.period_from,c.period_to,c.previous_earned_value,c.current_earned_value,c.cumulative_earned_value,c.current_progress_percentage,c.cumulative_progress_percentage,c.status,s.pricing_method,s.contract_value,CASE WHEN s.pricing_method=\'boq\' THEN CASE WHEN s.contract_value>0 THEN ROUND(COALESCE(c.cumulative_earned_value,0)*100/s.contract_value,4) ELSE 0 END ELSE COALESCE(c.cumulative_progress_percentage,0) END progress_percentage FROM subcontract_progress_certificates c JOIN subcontracts s ON s.id=c.subcontract_id' . $clause . ' ORDER BY ' . ($sort[$query->sortColumn] ?? 'c.certificate_date') . ' ' . $query->sortDirection . ',c.id ' . $query->sortDirection . ' LIMIT ? OFFSET ?', [...$params, $query->length, $query->start]);
        return ['recordsTotal' => (int) ($total['total'] ?? 0), 'recordsFiltered' => (int) ($filtered['total'] ?? 0), 'rows' => $result instanceof \mysqli_result ? $result->fetch_all(MYSQLI_ASSOC) : []];
    }

    private function one(string $sql, array $params = []): ?array
    {
        $result = $this->db->execute($sql, $params);
        $row = $result instanceof \mysqli_result ? $result->fetch_assoc() : null;
        return is_array($row) ? $row : null;
    }
}
