<?php
declare(strict_types=1);
namespace App\Modules\Subcontracts\Services;
use App\Core\Database\Database;
use App\Core\Exceptions\BusinessRuleException;
use App\Modules\Subcontracts\DTOs\SubcontractData;
use App\Modules\Subcontracts\Repositories\SubcontractRepository;
use App\Shared\Numbering\NumberGeneratorService;
final class SubcontractService
{
    private const TRANSITIONS=['draft'=>['active','cancelled'],'active'=>['suspended','completed','cancelled'],'suspended'=>['active','completed','cancelled'],'completed'=>[],'cancelled'=>[]];
    public function __construct(private readonly SubcontractRepository $repository,private readonly NumberGeneratorService $numbers,private readonly Database $db){}
    public function find(int $id):array{return $this->repository->find($id)??throw new BusinessRuleException('عقد مقاول الباطن غير موجود.');}
    public function refs():array{return $this->repository->refs();}
    public function vendorsForWorkSection(int $sectionId):array{return $this->repository->vendorsForWorkSection($sectionId);}
    public function all():array{return $this->repository->all();}
    public function workspaceStats(int $id):array{$this->find($id);return $this->repository->workspaceStats($id);}
    public function printScope(int $id):array{$this->find($id);return$this->repository->printScope($id);}
    public function scopeTotal(int $id):string{$this->find($id);return$this->repository->scopeTotal($id);}
    public function save(SubcontractData $data,?int $id=null,array $items=[]):int
    {
        $old=$id?$this->find($id):null;
        if(!$this->repository->project($data->projectId))throw new BusinessRuleException('المشروع غير موجود.');
        if(!$this->repository->workSection($data->workSectionId))throw new BusinessRuleException('مجال العمل غير موجود أو غير نشط.');
        $switch=$old===null||(int)$old['vendor_id']!==$data->vendorId;
        if(!$this->repository->vendor($data->vendorId,$switch))throw new BusinessRuleException('يجب اختيار مقاول باطن نشط.');
        if(!$this->repository->vendorInWorkSection($data->vendorId,$data->workSectionId))throw new BusinessRuleException('المقاول لا يعمل في مجال العمل المختار.');
        return $this->db->transaction(function()use($data,$id,$old,$items){
            if(!$id){
                $year=(int)substr($data->contractDate,0,4);
                $id=$this->repository->create($this->numbers->nextSubcontractCode($year),$data);
                $sectionId=$this->repository->createScope($id,$data->workSectionId);
                foreach($items as $index=>$item){$unit=$this->repository->unit($item->unitId)??throw new BusinessRuleException('الوحدة غير موجودة أو غير نشطة.');if(($unit['code']==='lump_sum')!==($item->pricingType==='lump_sum'))throw new BusinessRuleException('نوع تسعير البند لا يطابق الوحدة المختارة.');$this->repository->createScopeItem($sectionId,new \App\Modules\SubcontractBoq\DTOs\BoqItemData($sectionId,$item->description,$item->pricingType,$item->unitId,$item->quantity,$item->unitRate,$item->lumpSumAmount,$item->notes),$unit,$index+1);}
                return $id;
            }
            if($old['status']!=='draft')$this->assertLockedFields($old,$data);
            $this->repository->update($id,$data,$old['status']==='draft');
            return $id;
        });
    }
    public function change(int $id,string $status):void
    {
        $subcontract=$this->find($id);
        if(!in_array($status,self::TRANSITIONS[$subcontract['status']]??[],true))throw new BusinessRuleException('انتقال الحالة غير مسموح.');
        if($status==='active'){$boq=$this->repository->boq($id);if(!$boq||$boq['status']!=='approved')throw new BusinessRuleException('يجب اعتماد جدول الأعمال قبل التفعيل.');}
        $this->repository->status($id,$status);
    }
    public function transitions(string $status):array{return self::TRANSITIONS[$status]??[];}
    private function assertLockedFields(array $old,SubcontractData $data):void
    {
        foreach(['project_id'=>$data->projectId,'work_section_id'=>$data->workSectionId,'vendor_id'=>$data->vendorId,'contract_date'=>$data->contractDate,'title'=>$data->title,'description'=>$data->description]as$key=>$value)
            if(($old[$key]===null?null:(string)$old[$key])!==($value===null?null:(string)$value))throw new BusinessRuleException('لا يمكن تعديل البيانات التعاقدية بعد التفعيل.');
    }
}
