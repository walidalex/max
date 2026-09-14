<?php
declare(strict_types=1);
namespace App\Modules\Projects\Validators;
use App\Modules\Projects\DTOs\ProjectTableQuery;
final class ProjectTableQueryValidator
{
 public function validate(array $i):ProjectTableQuery
 {
  $cols=['project_code','name','client_name','project_type','manager_name','start_date','expected_end_date','status'];$index=max(0,(int)($i['order'][0]['column']??0));$type=(string)($i['project_type']??'');$status=(string)($i['status']??'');
  return new ProjectTableQuery(max(0,(int)($i['draw']??0)),max(0,(int)($i['start']??0)),min(100,max(10,(int)($i['length']??10))),mb_substr(trim((string)($i['search']['value']??'')),0,100),$cols[$index]??'project_code',strtolower((string)($i['order'][0]['dir']??'asc'))==='desc'?'DESC':'ASC',$this->id($i['client_id']??null),$this->id($i['project_manager_id']??null),in_array($type,ProjectValidator::TYPES,true)?$type:null,in_array($status,ProjectValidator::STATUSES,true)?$status:null);
 }
 private function id(mixed $v):?int{$id=filter_var($v,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);return $id===false?null:$id;}
}
