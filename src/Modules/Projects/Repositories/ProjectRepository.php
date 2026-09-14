<?php
declare(strict_types=1);
namespace App\Modules\Projects\Repositories;
use App\Core\Database\Database;
use App\Modules\Projects\DTOs\ProjectData;
use App\Modules\Projects\DTOs\ProjectTableQuery;
final class ProjectRepository
{
 public function __construct(private readonly Database $db){}
 public function find(int $id):?array
 {
  $sql="SELECT p.*,COALESCE(c.company_name,c.name) client_name,cc.name contact_name,u.name manager_name FROM projects p JOIN clients c ON c.id=p.client_id LEFT JOIN client_contacts cc ON cc.id=p.primary_contact_id LEFT JOIN users u ON u.id=p.project_manager_id WHERE p.id=? LIMIT 1";
  $r=$this->db->execute($sql,[$id]);$row=$r instanceof \mysqli_result?$r->fetch_assoc():null;return is_array($row)?$row:null;
 }
 public function clientExists(int $id):bool{return $this->exists('SELECT 1 FROM clients WHERE id=? LIMIT 1',[$id]);}
 public function contactBelongsTo(int $id,int $clientId):bool{return $this->exists('SELECT 1 FROM client_contacts WHERE id=? AND client_id=? LIMIT 1',[$id,$clientId]);}
 public function activeManagerExists(int $id):bool{return $this->exists('SELECT 1 FROM users WHERE id=? AND is_active=1 LIMIT 1',[$id]);}
 public function create(string $code,ProjectData $d):int
 {
  $sql='INSERT INTO projects(project_code,name,client_id,primary_contact_id,project_manager_id,project_type,status,start_date,expected_end_date,actual_end_date,site_address,city,area,description,notes) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
  $this->db->execute($sql,[$code,$d->name,$d->clientId,$d->primaryContactId,$d->projectManagerId,$d->projectType,$d->status,$d->startDate,$d->expectedEndDate,$d->actualEndDate,$d->siteAddress,$d->city,$d->area,$d->description,$d->notes]);return (int)$this->db->connection()->insert_id;
 }
 public function update(int $id,ProjectData $d):void
 {
  $sql='UPDATE projects SET name=?,client_id=?,primary_contact_id=?,project_manager_id=?,project_type=?,start_date=?,expected_end_date=?,actual_end_date=?,site_address=?,city=?,area=?,description=?,notes=? WHERE id=?';
  $this->db->execute($sql,[$d->name,$d->clientId,$d->primaryContactId,$d->projectManagerId,$d->projectType,$d->startDate,$d->expectedEndDate,$d->actualEndDate,$d->siteAddress,$d->city,$d->area,$d->description,$d->notes,$id]);
 }
 public function changeStatus(int $id,string $status):void{$this->db->execute('UPDATE projects SET status=? WHERE id=?',[$status,$id]);}
 public function clients():array{$r=$this->db->execute("SELECT id,client_code,COALESCE(company_name,name) name,is_active FROM clients ORDER BY COALESCE(company_name,name)");return $r instanceof \mysqli_result?$r->fetch_all(MYSQLI_ASSOC):[];}
 public function managers():array{$r=$this->db->execute('SELECT id,name,username FROM users WHERE is_active=1 ORDER BY name');return $r instanceof \mysqli_result?$r->fetch_all(MYSQLI_ASSOC):[];}
 public function contacts(int $clientId):array{$r=$this->db->execute('SELECT id,name,job_title,is_primary FROM client_contacts WHERE client_id=? ORDER BY is_primary DESC,name',[$clientId]);return $r instanceof \mysqli_result?$r->fetch_all(MYSQLI_ASSOC):[];}
 public function dataTable(ProjectTableQuery $q):array
 {
  $from=' FROM projects p JOIN clients c ON c.id=p.client_id LEFT JOIN users u ON u.id=p.project_manager_id';[$where,$params]=$this->filters($q);
  $t=$this->db->execute('SELECT COUNT(*) total FROM projects');$tr=$t instanceof \mysqli_result?$t->fetch_assoc():[];
  $f=$this->db->execute('SELECT COUNT(*) total'.$from.$where,$params);$fr=$f instanceof \mysqli_result?$f->fetch_assoc():[];
  $sort=['project_code'=>'p.project_code','name'=>'p.name','client_name'=>"COALESCE(c.company_name,c.name,'')",'project_type'=>'p.project_type','manager_name'=>"COALESCE(u.name,'')",'start_date'=>'p.start_date','expected_end_date'=>'p.expected_end_date','status'=>'p.status'];$order=$sort[$q->sortColumn]??'p.project_code';
  $sql="SELECT p.id,p.project_code,p.name,COALESCE(c.company_name,c.name) client_name,p.project_type,u.name manager_name,p.start_date,p.expected_end_date,p.status{$from}{$where} ORDER BY {$order} {$q->sortDirection} LIMIT ? OFFSET ?";
  $r=$this->db->execute($sql,[...$params,$q->length,$q->start]);return ['recordsTotal'=>(int)($tr['total']??0),'recordsFiltered'=>(int)($fr['total']??0),'rows'=>$r instanceof \mysqli_result?$r->fetch_all(MYSQLI_ASSOC):[]];
 }
 private function filters(ProjectTableQuery $q):array
 {
  $c=[];$p=[];if($q->search!==''){$s='%'.$q->search.'%';$c[]="(p.project_code LIKE ? OR p.name LIKE ? OR COALESCE(c.company_name,c.name,'') LIKE ? OR p.site_address LIKE ? OR p.city LIKE ?)";array_push($p,$s,$s,$s,$s,$s);}
  foreach(['client_id'=>$q->clientId,'project_manager_id'=>$q->projectManagerId,'project_type'=>$q->projectType,'status'=>$q->status] as $field=>$v){if($v!==null){$c[]='p.'.$field.'=?';$p[]=$v;}}
  return [$c===[]?'':' WHERE '.implode(' AND ',$c),$p];
 }
 private function exists(string $sql,array $params):bool{$r=$this->db->execute($sql,$params);return $r instanceof \mysqli_result&&$r->num_rows>0;}
}
