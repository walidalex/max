<?php

declare(strict_types=1);

namespace App\Modules\ClientProgressStatements\Repositories;

use App\Core\Database\Database;

final class CostPlusScopeRepository
{
    public function __construct(private readonly Database $db) {}

    public function items(int $projectId): array
    {
        $items = $this->rows("SELECT i.id item_id,i.description,i.pricing_type,i.quantity contract_quantity,i.unit_rate,i.lump_sum_amount,i.unit_symbol,i.unit_name,s.id subcontract_id,s.subcontract_code,w.id work_section_id,w.name work_section_name,v.name vendor_name FROM subcontract_boq_items i JOIN subcontract_boq_sections bs ON bs.id=i.subcontract_boq_section_id JOIN subcontract_boqs b ON b.id=bs.subcontract_boq_id JOIN subcontracts s ON s.id=b.subcontract_id JOIN work_sections w ON w.id=s.work_section_id JOIN vendors v ON v.id=s.vendor_id WHERE s.project_id=? AND s.pricing_method='boq' AND b.status='approved' ORDER BY w.sort_order,s.id,bs.sort_order,i.sort_order,i.id",[$projectId]);
        return array_merge($items, $this->rows("SELECT CONCAT('subcontract:',s.id) item_id,'subcontract' row_type,s.title description,'lump_sum' pricing_type,NULL contract_quantity,NULL unit_rate,s.contract_value lump_sum_amount,NULL unit_symbol,'مقطوعية' unit_name,s.id subcontract_id,s.subcontract_code,w.id work_section_id,w.name work_section_name,v.name vendor_name FROM subcontracts s JOIN work_sections w ON w.id=s.work_section_id JOIN vendors v ON v.id=s.vendor_id WHERE s.project_id=? AND s.pricing_method='lump_sum' AND s.status<>'draft' ORDER BY w.sort_order,s.id",[$projectId]));
    }

    public function lockProjectSubcontracts(int $projectId): void
    {
        $this->rows('SELECT id FROM subcontracts WHERE project_id=? ORDER BY id FOR UPDATE',[$projectId]);
    }
    public function expenseItems(int $projectId,string $date): array
    {
        return $this->rows("SELECT -CAST(c.id AS SIGNED) item_id,c.name description,c.cost_code,c.work_section_id,w.name work_section_name,'expense' row_type,'quantity' pricing_type,NULL contract_quantity,NULL unit_rate,NULL lump_sum_amount,'—' unit_name,NULL unit_symbol,'—' subcontract_code,'—' vendor_name FROM cost_codes c JOIN work_sections w ON w.id=c.work_section_id WHERE EXISTS(SELECT 1 FROM project_actual_costs x WHERE x.project_id=? AND x.cost_code_id=c.id AND x.expense_type='work' AND x.status='approved' AND x.cost_date<=?) ORDER BY w.sort_order,c.sort_order,c.id",[$projectId,$date]);
    }

    public function previous(int $contractId, ?int $statementId): ?array
    {
        $rows=$this->rows("SELECT statement_date,cost_scope_snapshot FROM client_progress_statements WHERE client_contract_id=? AND status='approved' AND (? IS NULL OR id<>?) ORDER BY statement_sequence DESC LIMIT 1",[$contractId,$statementId,$statementId]);
        return $rows[0]??null;
    }

    public function progress(int $projectId,string $date): array
    {
        return array_merge(
            $this->rows("SELECT pi.subcontract_boq_item_id item_id,CAST(SUM(CASE WHEN pi.pricing_type_snapshot='lump_sum' THEN pi.current_progress_percentage ELSE pi.current_quantity END) AS DECIMAL(18,4)) measure,CAST(SUM(pi.current_amount) AS DECIMAL(18,2)) earned FROM subcontract_progress_items pi JOIN subcontract_progress_certificates c ON c.id=pi.certificate_id JOIN subcontracts s ON s.id=c.subcontract_id WHERE s.project_id=? AND s.pricing_method='boq' AND c.status='approved' AND c.certificate_date<=? GROUP BY pi.subcontract_boq_item_id",[$projectId,$date]),
            $this->rows("SELECT CONCAT('subcontract:',s.id) item_id,CAST(SUM(c.current_progress_percentage) AS DECIMAL(18,4)) measure,CAST(SUM(c.current_earned_value) AS DECIMAL(18,2)) earned FROM subcontract_progress_certificates c JOIN subcontracts s ON s.id=c.subcontract_id WHERE s.project_id=? AND s.pricing_method='lump_sum' AND c.status='approved' AND c.certificate_date<=? GROUP BY s.id",[$projectId,$date]),
        );
    }

    public function earnedDelta(string $previous,string $earned): array
    {
        return $this->rows('SELECT CAST(? AS DECIMAL(18,2)) previous_subcontract_cost,GREATEST(CAST(? AS DECIMAL(18,2))-CAST(? AS DECIMAL(18,2)),0.00) current_subcontract_cost,GREATEST(CAST(? AS DECIMAL(18,2)),CAST(? AS DECIMAL(18,2))) cumulative_subcontract_cost',[$previous,$earned,$previous,$previous,$earned])[0];
    }

    public function costs(int $contractId,?int $statementId,string $date): array
    {
        if($statementId===null){
return $this->rows("SELECT CASE WHEN x.expense_type='work' THEN -CAST(x.cost_code_id AS SIGNED) WHEN x.expense_type='general' THEN 0 ELSE COALESCE(x.subcontract_boq_item_id,0) END item_id,CAST(SUM(CASE WHEN d.id IS NULL AND x.cost_date<=? THEN x.amount ELSE 0 END) AS DECIMAL(18,2)) current_cost,CAST(SUM(CASE WHEN d.id IS NOT NULL THEN d.amount_snapshot ELSE 0 END) AS DECIMAL(18,2)) previous_cost FROM project_actual_costs x JOIN client_contracts cc ON cc.project_id=x.project_id LEFT JOIN client_progress_statement_costs d ON d.project_actual_cost_id=x.id AND d.is_committed=1 WHERE cc.id=? AND x.status='approved' GROUP BY CASE WHEN x.expense_type='work' THEN -CAST(x.cost_code_id AS SIGNED) WHEN x.expense_type='general' THEN 0 ELSE COALESCE(x.subcontract_boq_item_id,0) END",[$date,$contractId]);
        }
        return $this->rows("SELECT CASE WHEN x.expense_type='work' THEN -CAST(x.cost_code_id AS SIGNED) WHEN x.expense_type='general' THEN 0 ELSE COALESCE(x.subcontract_boq_item_id,0) END item_id,CAST(SUM(CASE WHEN d.statement_id=? THEN CASE WHEN d.is_committed=1 THEN d.amount_snapshot ELSE x.amount END ELSE 0 END) AS DECIMAL(18,2)) current_cost,CAST(SUM(CASE WHEN d.statement_id<>COALESCE(?,0) AND d.is_committed=1 AND s.status='approved' THEN d.amount_snapshot ELSE 0 END) AS DECIMAL(18,2)) previous_cost FROM client_progress_statement_costs d JOIN client_progress_statements s ON s.id=d.statement_id JOIN project_actual_costs x ON x.id=d.project_actual_cost_id WHERE s.client_contract_id=? AND (d.statement_id=? OR (d.is_committed=1 AND s.status='approved')) GROUP BY CASE WHEN x.expense_type='work' THEN -CAST(x.cost_code_id AS SIGNED) WHEN x.expense_type='general' THEN 0 ELSE COALESCE(x.subcontract_boq_item_id,0) END",[$statementId,$statementId,$contractId,$statementId]);
    }

    public function calculate(array $item,string $previous,string $cumulative,string $previousCost,string $currentCost): array
    {
        return $this->rows('SELECT CAST(? AS DECIMAL(18,4)) previous_measure,CAST(? AS DECIMAL(18,4))-CAST(? AS DECIMAL(18,4)) current_measure,CAST(? AS DECIMAL(18,4)) cumulative_measure,ROUND(CAST(? AS DECIMAL(18,4))*100/NULLIF(CAST(? AS DECIMAL(18,4)),0),4) progress_percentage,CAST(? AS DECIMAL(18,2)) previous_cost,CAST(? AS DECIMAL(18,2)) current_cost,CAST(? AS DECIMAL(18,2))+CAST(? AS DECIMAL(18,2)) cumulative_cost',[$previous,$cumulative,$previous,$cumulative,$cumulative,$item['pricing_type']==='lump_sum'?'100':($item['contract_quantity']??'0'),$previousCost,$currentCost,$previousCost,$currentCost])[0];
    }

    public function freeze(int $statementId,array $rows): void
    {
        $this->db->execute("UPDATE client_progress_statements SET cost_scope_snapshot=? WHERE id=? AND status='draft'",[json_encode($rows,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),$statementId]);
    }
    public function add(string $a,string $b):string
    {
        return (string)$this->rows('SELECT CAST(? AS DECIMAL(18,2))+CAST(? AS DECIMAL(18,2)) total',[$a,$b])[0]['total'];
    }
    public function withMarkup(string $cost,string $rate):string
    {
        return (string)$this->rows('SELECT CAST(? AS DECIMAL(18,2))+ROUND(CAST(? AS DECIMAL(18,2))*CAST(? AS DECIMAL(7,4))/100,2) total',[$cost,$cost,$rate])[0]['total'];
    }

    private function rows(string $sql,array $params): array
    {
        $r=$this->db->execute($sql,$params);
        return $r instanceof \mysqli_result?$r->fetch_all(MYSQLI_ASSOC):[];
    }
}
