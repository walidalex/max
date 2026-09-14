<?php
declare(strict_types=1);
namespace App\Modules\CostStructure\Controllers;
use App\Core\Exceptions\BusinessRuleException;
use App\Core\Exceptions\ValidationException;
use App\Core\Http\Csrf;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\View\View;
use App\Modules\AccessControl\Services\AuthorizationService;
use App\Modules\CostStructure\Services\CostStructureService;
use App\Modules\CostStructure\Validators\CostCodeValidator;
use App\Modules\CostStructure\Validators\UnitValidator;
use App\Modules\CostStructure\Validators\WorkSectionValidator;
final class CostStructureController
{
    public function __construct(private readonly View $view,private readonly Csrf $csrf,private readonly AuthorizationService $auth,private readonly CostStructureService $service,private readonly WorkSectionValidator $sectionValidator,private readonly CostCodeValidator $codeValidator,private readonly UnitValidator $unitValidator) {}
    public function index(Request $request): Response { return Response::html($this->view->render('modules/cost-structure/index',['title'=>'هيكل التكاليف',...$this->service->catalogue(),'canManage'=>$this->auth->can('cost_structure.manage')])); }
    public function storeSection(Request $r): Response { return $this->execute($r,fn()=> $this->service->saveSection($this->sectionValidator->validate($r->all()))); }
    public function updateSection(Request $r): Response { return $this->execute($r,fn()=> $this->service->saveSection($this->sectionValidator->validate($r->all()),(int)$r->route('id'))); }
    public function toggleSection(Request $r): Response { return $this->execute($r,fn()=> $this->service->setSectionActive((int)$r->route('id'),$this->active($r))); }
    public function storeCode(Request $r): Response { return $this->execute($r,fn()=> $this->service->saveCode($this->codeValidator->validate($r->all()))); }
    public function updateCode(Request $r): Response { return $this->execute($r,fn()=> $this->service->saveCode($this->codeValidator->validate($r->all()),(int)$r->route('id'))); }
    public function toggleCode(Request $r): Response { return $this->execute($r,fn()=> $this->service->setCodeActive((int)$r->route('id'),$this->active($r))); }
    public function storeUnit(Request $r): Response { return $this->execute($r,fn()=> $this->service->saveUnit($this->unitValidator->validate($r->all()))); }
    public function updateUnit(Request $r): Response { return $this->execute($r,fn()=> $this->service->saveUnit($this->unitValidator->validate($r->all()),(int)$r->route('id'))); }
    public function toggleUnit(Request $r): Response { return $this->execute($r,fn()=> $this->service->setUnitActive((int)$r->route('id'),$this->active($r))); }
    private function execute(Request $r,callable $action): Response
    {
        if(!$this->csrf->isValid($r->input('_token')))return Response::json(['message'=>'انتهت صلاحية الطلب. أعد تحميل الصفحة.'],419);
        try{$action();return Response::json(['message'=>'تم حفظ التغييرات بنجاح.']);}
        catch(ValidationException $e){return Response::json(['message'=>'يرجى مراجعة البيانات المدخلة.','errors'=>$e->errors],422);}
        catch(BusinessRuleException $e){return Response::json(['message'=>$e->getMessage()],400);}
    }
    private function active(Request $r): bool { return filter_var($r->input('is_active'),FILTER_VALIDATE_BOOL,FILTER_NULL_ON_FAILURE)??false; }
}
