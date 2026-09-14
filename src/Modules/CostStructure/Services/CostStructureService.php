<?php
declare(strict_types=1);
namespace App\Modules\CostStructure\Services;
use App\Core\Exceptions\BusinessRuleException;
use App\Modules\CostStructure\DTOs\CostCodeData;
use App\Modules\CostStructure\DTOs\UnitData;
use App\Modules\CostStructure\DTOs\WorkSectionData;
use App\Modules\CostStructure\Repositories\CostCodeRepository;
use App\Modules\CostStructure\Repositories\UnitRepository;
use App\Modules\CostStructure\Repositories\WorkSectionRepository;
final class CostStructureService
{
    public function __construct(private readonly WorkSectionRepository $sections,private readonly CostCodeRepository $codes,private readonly UnitRepository $units) {}
    public function catalogue(): array
    {
        $sections=$this->sections->all(); foreach($sections as &$section){$section['codes']=[];} unset($section);
        $indexes=[]; foreach($sections as $i=>$section){$indexes[(int)$section['id']]=$i;}
        foreach($this->codes->all() as $code){$sectionId=(int)$code['work_section_id']; if(isset($indexes[$sectionId])){$sections[$indexes[$sectionId]]['codes'][]=$code;}}
        return ['sections'=>$sections,'units'=>$this->units->all()];
    }
    public function saveSection(WorkSectionData $data,?int $id=null): int { if($this->sections->codeExists($data->sectionCode,$id))throw new BusinessRuleException('كود قسم الأعمال مستخدم بالفعل.'); if($id===null)return $this->sections->create($data); $this->requireSection($id); $this->sections->update($id,$data); return $id; }
    public function saveUnit(UnitData $data,?int $id=null): int { if($this->units->codeExists($data->code,$id))throw new BusinessRuleException('كود الوحدة مستخدم بالفعل.'); if($id===null)return $this->units->create($data); $this->requireUnit($id); $this->units->update($id,$data); return $id; }
    public function saveCode(CostCodeData $data,?int $id=null): int
    {
        if($this->codes->codeExists($data->costCode,$id))throw new BusinessRuleException('كود التكلفة مستخدم بالفعل.');
        $existing=$id===null?null:$this->requireCode($id);
        $section=$this->requireSection($data->workSectionId); if(!(bool)$section['is_active']&&($existing===null||(int)$existing['work_section_id']!==$data->workSectionId))throw new BusinessRuleException('لا يمكن اختيار قسم أعمال غير نشط لمعاملة جديدة.');
        if($data->defaultUnitId!==null){$unit=$this->requireUnit($data->defaultUnitId); if(!(bool)$unit['is_active']&&($existing===null||(int)$existing['default_unit_id']!==$data->defaultUnitId))throw new BusinessRuleException('لا يمكن اختيار وحدة غير نشطة لتعيين جديد.');}
        if($id===null)return $this->codes->create($data); $this->codes->update($id,$data); return $id;
    }
    public function setSectionActive(int $id,bool $active): void { $this->requireSection($id); $this->sections->setActive($id,$active); }
    public function setUnitActive(int $id,bool $active): void { $this->requireUnit($id); $this->units->setActive($id,$active); }
    public function setCodeActive(int $id,bool $active): void { $code=$this->requireCode($id); if($active){$section=$this->requireSection((int)$code['work_section_id']); if(!(bool)$section['is_active'])throw new BusinessRuleException('لا يمكن تفعيل كود تكلفة تابع لقسم غير نشط.');} $this->codes->setActive($id,$active); }
    private function requireSection(int $id): array { return $this->sections->find($id)??throw new BusinessRuleException('قسم الأعمال غير موجود.'); }
    private function requireUnit(int $id): array { return $this->units->find($id)??throw new BusinessRuleException('الوحدة غير موجودة.'); }
    private function requireCode(int $id): array { return $this->codes->find($id)??throw new BusinessRuleException('كود التكلفة غير موجود.'); }
}
