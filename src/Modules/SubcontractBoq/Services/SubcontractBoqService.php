<?php
declare(strict_types=1);
namespace App\Modules\SubcontractBoq\Services;
use App\Core\Auth\Auth;
use App\Core\Database\Database;
use App\Core\Exceptions\BusinessRuleException;
use App\Modules\SubcontractBoq\DTOs\BoqItemData;
use App\Modules\SubcontractBoq\Repositories\SubcontractBoqRepository;
final class SubcontractBoqService
{
    public function __construct(private readonly SubcontractBoqRepository $repository,private readonly Database $db,private readonly Auth $auth){}
    public function page(int $contractId):array{$contract=$this->contract($contractId);$boq=$this->repository->boq($contractId);return['contract'=>$contract,'boq'=>$boq,'sections'=>$boq?$this->repository->sections((int)$boq['id']):[],'total'=>$boq?$this->repository->total((int)$boq['id']):'0.00','masters'=>$this->repository->masters((int)$contract['work_section_id'])];}
    public function saveItem(int $contractId,BoqItemData $data,?int $id=null):void
    {
        $this->draftBoq($contractId);$this->assertDraftContract($this->contract($contractId));$section=$this->ownedSection($contractId,$data->sectionId);
        $unit=$this->repository->activeUnit($data->unitId);
        if(!$unit)throw new BusinessRuleException('الوحدة غير نشطة أو غير موجودة.');
        if($data->pricingType==='quantity'&&($unit['code']??null)==='lump_sum')throw new BusinessRuleException('وحدة المقطوعية لا تحتاج كمية أو سعر وحدة.');
        if($data->pricingType==='lump_sum'&&($unit['code']??null)!=='lump_sum')throw new BusinessRuleException('يجب اختيار وحدة مقطوعية للبند المقطوعي.');
        if($data->pricingType==='quantity'&&($unit===null||$data->quantity===null||$data->unitRate===null))throw new BusinessRuleException('البند الكمي يجب أن يحتوي على الوحدة والكمية وسعر الوحدة.');
        if($data->pricingType==='lump_sum'&&$data->lumpSumAmount===null)throw new BusinessRuleException('قيمة البند المقطوعي مطلوبة.');
        if($id===null)$this->repository->addItem($data,$unit);else{$this->ownedItem($contractId,$id);$this->repository->updateItem($id,$data,$unit);}
    }
    public function removeItem(int $contractId,int $id):void{$this->draftBoq($contractId);$this->assertDraftContract($this->contract($contractId));$this->ownedItem($contractId,$id);$this->repository->removeItem($id);}
    public function approve(int $contractId):void{$boq=$this->draftBoq($contractId);$contract=$this->contract($contractId);$this->assertDraftContract($contract);if($this->repository->countItems((int)$boq['id'])<1)throw new BusinessRuleException('لا يمكن اعتماد جدول أعمال بلا بنود.');if($this->repository->incompleteItems((int)$boq['id'])>0)throw new BusinessRuleException('جميع البنود يجب أن تكون مسعرة وفق نوعها: كمي أو مقطوعية.');$total=$this->repository->total((int)$boq['id']);$user=$this->auth->id()??throw new BusinessRuleException('تعذر تحديد المستخدم الحالي.');$this->db->transaction(function()use($boq,$user,$total):void{$state=$this->repository->approvalState((int)$boq['id']);if(!$state||$state['boq_status']!=='draft'||$state['contract_status']!=='draft')throw new BusinessRuleException('تغيرت حالة العقد أو جدول الأعمال. حدّث الصفحة ثم حاول مرة أخرى.');$this->repository->approve((int)$boq['id'],$user,$total,true);});}
    private function contract(int $id):array{return $this->repository->contract($id)??throw new BusinessRuleException('عقد مقاول الباطن غير موجود.');}
    private function assertDraftContract(array $contract):void{if($contract['status']!=='draft')throw new BusinessRuleException('لا يمكن تعديل النطاق الأصلي بعد تفعيل العقد.');}
    private function draftBoq(int $contractId):array{$boq=$this->repository->boq($contractId)??throw new BusinessRuleException('لم يتم بدء نطاق العقد.');if($boq['status']!=='draft')throw new BusinessRuleException('جدول الأعمال المعتمد غير قابل للتعديل.');return $boq;}
    private function ownedSection(int $contractId,int $id):array{$section=$this->repository->section($id);if(!$section||(int)$section['subcontract_id']!==$contractId)throw new BusinessRuleException('القسم لا يتبع هذا العقد.');return $section;}
    private function ownedItem(int $contractId,int $id):array{$item=$this->repository->item($id);if(!$item||(int)$item['subcontract_id']!==$contractId)throw new BusinessRuleException('البند لا يتبع هذا العقد.');return $item;}
}
