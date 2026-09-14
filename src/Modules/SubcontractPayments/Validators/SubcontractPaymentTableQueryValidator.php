<?php

declare(strict_types=1);

namespace App\Modules\SubcontractPayments\Validators;

use App\Modules\SubcontractPayments\DTOs\SubcontractPaymentTableQuery;

final class SubcontractPaymentTableQueryValidator
{
    public function validate(array $input, int $subcontractId): SubcontractPaymentTableQuery
    {
        $columns = ['payment_code','payment_date','vendor_name','project_name','amount','payment_method','reference_number','approved_progress_percentage_snapshot','approved_earned_value_snapshot','status','created_by_name','posted_by_name'];
        $index = max(0, (int) ($input['order'][0]['column'] ?? 1));
        $status = (string) ($input['status'] ?? ''); $method = (string) ($input['payment_method'] ?? '');
        return new SubcontractPaymentTableQuery(max(0,(int)($input['draw']??0)),max(0,(int)($input['start']??0)),min(100,max(10,(int)($input['length']??10))),mb_substr(trim((string)($input['search']['value']??'')),0,100),$columns[$index]??'payment_date',strtolower((string)($input['order'][0]['dir']??'desc'))==='asc'?'ASC':'DESC',max(1,$subcontractId),in_array($status,['draft','posted','cancelled'],true)?$status:null,in_array($method,SubcontractPaymentValidator::METHODS,true)?$method:null,$this->date($input['date_from']??null),$this->date($input['date_to']??null));
    }
    private function date(mixed $value): ?string { $value=trim((string)$value);$date=\DateTimeImmutable::createFromFormat('!Y-m-d',$value);return$date&&$date->format('Y-m-d')===$value?$value:null; }
}
