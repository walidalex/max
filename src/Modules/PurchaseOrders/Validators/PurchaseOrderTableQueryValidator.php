<?php
declare(strict_types=1);
namespace App\Modules\PurchaseOrders\Validators;
use App\Modules\PurchaseOrders\DTOs\PurchaseOrderTableQuery;
final class PurchaseOrderTableQueryValidator
{
    public function validate(array $input):PurchaseOrderTableQuery{$columns=['po_code','po_date','expected_date','vendor_name','project_name','total_amount','status','approved_by_name'];$order=(array)($input['order'][0]??[]);$index=(int)($order['column']??1);$id=static fn(mixed $value):?int=>filter_var($value,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])?:null;$nullable=static fn(mixed $value):?string=>trim((string)$value)===''?null:trim((string)$value);return new PurchaseOrderTableQuery(max(0,(int)($input['draw']??0)),max(0,(int)($input['start']??0)),min(100,max(10,(int)($input['length']??10))),trim((string)($input['search']['value']??'')),$columns[$index]??'po_date',strtolower((string)($order['dir']??'desc'))==='asc'?'ASC':'DESC',$id($input['vendor_id']??null),$id($input['project_id']??null),in_array($input['status']??null,['draft','approved','cancelled'],true)?$input['status']:null,$nullable($input['date_from']??null),$nullable($input['date_to']??null));}
}
