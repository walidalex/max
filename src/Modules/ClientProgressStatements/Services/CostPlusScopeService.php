<?php

declare(strict_types=1);

namespace App\Modules\ClientProgressStatements\Services;

use App\Modules\ClientProgressStatements\Repositories\CostPlusScopeRepository;

final class CostPlusScopeService
{
    public function __construct(private readonly CostPlusScopeRepository $scope) {}

    public function rows(array $contract,string $date,?int $statementId=null,?string $snapshot=null,bool $lock=false): array
    {
        if($snapshot!==null)return json_decode($snapshot,true,512,JSON_THROW_ON_ERROR);
        if($contract['pricing_method']!=='cost_plus')return [];
        if($lock)$this->scope->lockProjectSubcontracts((int)$contract['project_id']);
        $previous=$this->scope->previous((int)$contract['id'],$statementId);
        $previousRows=empty($previous['cost_scope_snapshot'])?[]:json_decode($previous['cost_scope_snapshot'],true,512,JSON_THROW_ON_ERROR);
        $old=array_column($previousRows,null,'item_id');
        $execution=array_column($this->scope->progress((int)$contract['project_id'],$date),null,'item_id');
        $progress=array_column($execution,'measure','item_id');
        $priorProgress=$previous===null?[]:array_column($this->scope->progress((int)$contract['project_id'],$previous['statement_date']),'measure','item_id');
        $costs=array_column($this->scope->costs((int)$contract['id'],$statementId,$date),null,'item_id');
        $items=array_column($this->scope->items((int)$contract['project_id']),null,'item_id');
        foreach($this->scope->expenseItems((int)$contract['project_id'],$date) as $expense)$items[$expense['item_id']]=$expense;
        // Keep historical rows if the source master list changes.
        foreach($old as $id=>$item){
            if($id===0||isset($items[$id]))continue;
            // Older snapshots included unused expense classifications; do not carry those empty rows forward.
            if($id<0&&(float)($item['cumulative_cost']??0)===0.0&&(float)($costs[$id]['previous_cost']??0)===0.0&&(float)($costs[$id]['current_cost']??0)===0.0)continue;
            $items[$id]=$item;
        }
        $items[0]=['item_id'=>0,'description'=>'مصروفات عامة / غير موزعة','work_section_name'=>'مصروفات عامة / غير موزعة','subcontract_code'=>'—','vendor_name'=>'—','pricing_type'=>'quantity','contract_quantity'=>null,'unit_name'=>'—','unit_symbol'=>null,'unit_rate'=>null,'lump_sum_amount'=>null];
        uasort($items,static function(array $a,array $b):int{
            return [(int)($a['item_id']===0?PHP_INT_MAX:($a['work_section_id']??0)),($a['row_type']??'scope')==='expense'?1:0,abs((int)$a['item_id'])]
                <=> [(int)($b['item_id']===0?PHP_INT_MAX:($b['work_section_id']??0)),($b['row_type']??'scope')==='expense'?1:0,abs((int)$b['item_id'])];
        });
        $rows=[];
        foreach($items as $id=>$item){
            unset($item['group_totals']);
            $prior=(string)($old[$id]['cumulative_measure']??$priorProgress[$id]??'0.0000');
            $total=(string)($progress[$id]??$prior);
            $isSubcontract=(int)$id>0||($item['row_type']??'')==='subcontract';
            $billing=[];
            $previousCost=(string)($costs[$id]['previous_cost']??'0.00');
            $currentCost=(string)($costs[$id]['current_cost']??'0.00');
            if($isSubcontract){
                // Earlier statements billed linked actual costs only; their committed amounts are the migration baseline.
                $billed=(string)($old[$id]['cumulative_subcontract_cost']??$previousCost);
                $billing=$this->scope->earnedDelta($billed,(string)($execution[$id]['earned']??'0.00'));
                $previousCost=$billing['previous_subcontract_cost'];
                $currentCost=$billing['current_subcontract_cost'];
            }
            $rows[]=array_merge($item,$billing,$this->scope->calculate($item,$prior,$total,$previousCost,$currentCost));
        }
        $groups=[];
        foreach($rows as $index=>$row){
            $key=$row['work_section_name'];
            foreach(['previous_cost','current_cost','cumulative_cost'] as $field)$groups[$key][$field]=$this->scope->add($groups[$key][$field]??'0.00',(string)$row[$field]);
            $groups[$key]['last']=$index;
        }
        foreach($groups as $group){
            $last=$group['last'];unset($group['last']);
            foreach(['previous_cost','current_cost','cumulative_cost'] as $field)$group[$field.'_with_markup']=$this->scope->withMarkup($group[$field],(string)$contract['markup_percentage']);
            $group['markup_percentage']=(string)$contract['markup_percentage'];
            $rows[$last]['group_totals']=$group;
        }
        return $rows;
    }

    public function subcontractTotal(array $rows): string
    {
        $total='0.00';
        foreach($rows as $row)$total=$this->scope->add($total,(string)($row['current_subcontract_cost']??'0.00'));
        return $total;
    }

    public function freezeRows(int $id,array $rows): void
    {
        $this->scope->freeze($id,$rows);
    }
}
