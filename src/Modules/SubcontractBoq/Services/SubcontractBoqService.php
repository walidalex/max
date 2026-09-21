<?php
declare(strict_types=1);
namespace App\Modules\SubcontractBoq\Services;
use App\Core\Auth\Auth;
use App\Core\Database\Database;
use App\Core\Exceptions\BusinessRuleException;
use App\Modules\SubcontractBoq\DTOs\BoqItemData;
use App\Modules\SubcontractBoq\DTOs\BoqSectionData;
use App\Modules\SubcontractBoq\Repositories\SubcontractBoqRepository;
final class SubcontractBoqService
{
    public function __construct(private readonly SubcontractBoqRepository $repository,private readonly Database $db,private readonly Auth $auth){}
    public function page(int $contractId):array{$contract=$this->contract($contractId);$boq=$this->repository->boq($contractId);return['contract'=>$contract,'boq'=>$boq,'sections'=>$boq?$this->repository->sections((int)$boq['id']):[],'total'=>$boq?$this->repository->total((int)$boq['id']):'0.00','masters'=>$this->repository->masters()];}
    public function start(int $contractId):void{$contract=$this->contract($contractId);$this->assertDraftContract($contract);if($this->repository->boq($contractId))throw new BusinessRuleException('تم بدء نطاق العقد بالفعل.');$this->repository->create($contractId);}
    public function saveSection(int $contractId,BoqSectionData $data,?int $id=null):void{$boq=$this->draftBoq($contractId);if($data->workSectionId!==null&&!$this->repository->activeWorkSection($data->workSectionId))throw new BusinessRuleException('قسم الأعمال غير نشط أو غير موجود.');if($id===null)$this->repository->addSection((int)$boq['id'],$data);else{$section=$this->ownedSection($contractId,$id);$this->repository->updateSection((int)$section['id'],$data);}}
    public function saveItem(int $contractId,BoqItemData $data,?int $id=null):void
    {
        $this->draftBoq($contractId);$this->ownedSection($contractId,$data->sectionId);
        $unit=$data->unitId===null?null:$this->repository->activeUnit($data->unitId);
        if($data->unitId!==null&&!$unit)throw new BusinessRuleException('الوحدة غير نشطة أو غير موجودة.');
        if($data->costCodeId!==null&&!$this->repository->activeCostCode($data->costCodeId))throw new BusinessRuleException('كود التكلفة غير متاح.');
        if($data->pricingType==='quantity'&&($unit===null||$data->quantity===null||$data->unitRate===null))throw new BusinessRuleException('البند الكمي يجب أن يحتوي على الوحدة والكمية وسعر الوحدة.');
        if($data->pricingType==='lump_sum'&&$data->lumpSumAmount===null)throw new BusinessRuleException('قيمة البند المقطوعي مطلوبة.');
        if($id===null)$this->repository->addItem($data,$unit);else{$this->ownedItem($contractId,$id);$this->repository->updateItem($id,$data,$unit);}
    }
    public function removeSection(int $contractId,int $id):void{$this->draftBoq($contractId);$this->ownedSection($contractId,$id);$this->db->transaction(fn()=>[$this->repository->removeItems($id),$this->repository->removeSection($id)]);}
    public function removeItem(int $contractId,int $id):void{$this->draftBoq($contractId);$this->ownedItem($contractId,$id);$this->repository->removeItem($id);}
    public function discard(int $contractId):void{$boq=$this->draftBoq($contractId);$this->assertDraftContract($this->contract($contractId));if($this->repository->countItems((int)$boq['id'])>0||$this->repository->sections((int)$boq['id'])!==[])throw new BusinessRuleException('يمكن حذف نطاق اختياري فارغ فقط. احذف الأقسام أولًا.');$this->repository->discard((int)$boq['id']);}
    public function approve(int $contractId):void{$boq=$this->draftBoq($contractId);$contract=$this->contract($contractId);$this->assertDraftContract($contract);if($this->repository->countItems((int)$boq['id'])<1)throw new BusinessRuleException('لا يمكن اعتماد جدول أعمال بلا بنود.');if($this->repository->incompleteItems((int)$boq['id'])>0)throw new BusinessRuleException('جميع البنود يجب أن تكون مسعرة وفق نوعها: كمي أو مقطوعية.');$total=$this->repository->total((int)$boq['id']);$user=$this->auth->id()??throw new BusinessRuleException('تعذر تحديد المستخدم الحالي.');$this->db->transaction(fn()=>$this->repository->approve((int)$boq['id'],$user,$total,true));}
    private function contract(int $id):array{return $this->repository->contract($id)??throw new BusinessRuleException('عقد مقاول الباطن غير موجود.');}
    private function assertDraftContract(array $contract):void{if($contract['status']!=='draft')throw new BusinessRuleException('لا يمكن تعديل النطاق الأصلي بعد تفعيل العقد.');}
    private function draftBoq(int $contractId):array{$boq=$this->repository->boq($contractId)??throw new BusinessRuleException('لم يتم بدء نطاق العقد.');if($boq['status']!=='draft')throw new BusinessRuleException('جدول الأعمال المعتمد غير قابل للتعديل.');return $boq;}
    private function ownedSection(int $contractId,int $id):array{$section=$this->repository->section($id);if(!$section||(int)$section['subcontract_id']!==$contractId)throw new BusinessRuleException('القسم لا يتبع هذا العقد.');return $section;}
    private function ownedItem(int $contractId,int $id):array{$item=$this->repository->item($id);if(!$item||(int)$item['subcontract_id']!==$contractId)throw new BusinessRuleException('البند لا يتبع هذا العقد.');return $item;}
}
