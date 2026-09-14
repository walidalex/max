<?php
declare(strict_types=1);
namespace App\Modules\Projects\Services;
use App\Core\Database\Database;use App\Core\Exceptions\BusinessRuleException;use App\Modules\Projects\DTOs\ProjectData;use App\Modules\Projects\DTOs\ProjectTableQuery;use App\Modules\Projects\Repositories\ProjectRepository;use App\Shared\Numbering\NumberGeneratorService;
final class ProjectService
{
 private const TRANSITIONS=['planning'=>['active','cancelled'],'active'=>['on_hold','completed','cancelled'],'on_hold'=>['active','cancelled'],'completed'=>[],'cancelled'=>[]];
 public function __construct(private readonly ProjectRepository $projects,private readonly NumberGeneratorService $numbers,private readonly Database $db){}
 public function find(int $id):array{return $this->projects->find($id)??throw new BusinessRuleException('المشروع غير موجود.');}
 public function save(ProjectData $d,?int $id=null):int{$this->relations($d);return $this->db->transaction(function()use($d,$id){if($id===null)return $this->projects->create($this->numbers->nextProjectCode((int)date('Y')),$d);$this->find($id);$this->projects->update($id,$d);return $id;});}
 public function changeStatus(int $id,string $status):void{$p=$this->find($id);$current=(string)$p['status'];if(!in_array($status,self::TRANSITIONS[$current]??[],true))throw new BusinessRuleException('لا يمكن تنفيذ انتقال حالة المشروع المطلوب.');$this->projects->changeStatus($id,$status);}
 public function allowedTransitions(string $status):array{return self::TRANSITIONS[$status]??[];}
 public function referenceData():array{return ['clients'=>$this->projects->clients(),'managers'=>$this->projects->managers()];}
 public function contacts(int $clientId):array{if(!$this->projects->clientExists($clientId))throw new BusinessRuleException('العميل غير موجود.');return $this->projects->contacts($clientId);}
 public function dataTable(ProjectTableQuery $q):array{$r=$this->projects->dataTable($q);return ['draw'=>$q->draw,'recordsTotal'=>$r['recordsTotal'],'recordsFiltered'=>$r['recordsFiltered'],'data'=>$r['rows']];}
 private function relations(ProjectData $d):void
 {
  if(!$this->projects->clientExists($d->clientId))throw new BusinessRuleException('العميل المحدد غير موجود.');
  if($d->primaryContactId!==null&&!$this->projects->contactBelongsTo($d->primaryContactId,$d->clientId))throw new BusinessRuleException('جهة الاتصال لا تتبع العميل المحدد.');
  if($d->projectManagerId!==null&&!$this->projects->activeManagerExists($d->projectManagerId))throw new BusinessRuleException('مدير المشروع المحدد غير موجود أو غير فعال.');
 }
}
