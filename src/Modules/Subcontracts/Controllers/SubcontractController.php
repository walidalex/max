<?php
declare(strict_types=1);
namespace App\Modules\Subcontracts\Controllers;
use App\Core\Exceptions\BusinessRuleException;
use App\Core\Exceptions\ValidationException;
use App\Core\Http\Csrf;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\Session;
use App\Core\View\View;
use App\Modules\AccessControl\Services\AuthorizationService;
use App\Modules\SubcontractPayments\Services\SubcontractPaymentService;
use App\Modules\Subcontracts\Services\SubcontractService;
use App\Modules\Subcontracts\Validators\SubcontractValidator;
use App\Modules\Vendors\Services\VendorService;
final class SubcontractController
{
    public function __construct(private readonly View $view,private readonly Csrf $csrf,private readonly Session $session,private readonly SubcontractService $service,private readonly SubcontractValidator $validator,private readonly AuthorizationService $authorization,private readonly VendorService $vendors,private readonly SubcontractPaymentService $payments){}
    public function index(Request $request):Response{return Response::html($this->view->render('modules/subcontracts/index',['title'=>'عقود مقاولي الباطن','rows'=>$this->service->all()]));}
    public function create(Request $request):Response{return $this->form(null);}
    public function edit(Request $request):Response{return $this->form((int)$request->route('id'));}
    public function show(Request $request):Response
    {
        $id=(int)$request->route('id');
        $subcontract=$this->service->find($id);
        return Response::html($this->view->render('modules/subcontracts/show',[
            'title'=>'مساحة عمل عقد مقاول الباطن',
            'subcontract'=>$subcontract,
            'transitions'=>$this->service->transitions($subcontract['status']),
            'stats'=>$this->service->workspaceStats($id),
            'summary'=>$this->payments->financialSummary($id),
            'permissions'=>[
                'edit'=>$this->authorization->can('subcontracts.edit'),
                'change_status'=>$this->authorization->can('subcontracts.change_status'),
                'boq_view'=>$this->authorization->can('subcontract_boq.view'),
                'boq_manage'=>$this->authorization->can('subcontract_boq.manage'),
                'certificate_view'=>$this->authorization->can('subcontract_certificates.view'),
                'certificate_create'=>$this->authorization->can('subcontract_certificates.create'),
                'payment_view'=>$this->authorization->can('subcontract_payments.view'),
                'payment_create'=>$this->authorization->can('subcontract_payments.create'),
            ],
        ]));
    }
    public function store(Request $request):Response{return $this->save($request,null);}
    public function update(Request $request):Response{return $this->save($request,(int)$request->route('id'));}
    public function vendorsByWorkSection(Request $request):Response{return Response::json(['data'=>$this->service->vendorsForWorkSection((int)$request->input('work_section_id'))]);}
    public function status(Request $request):Response
    {
        if(!$this->csrf->isValid($request->input('_token')))return Response::html('انتهت صلاحية الطلب.',419);
        $id=(int)$request->route('id');
        try{$this->service->change($id,(string)$request->input('status'));$this->flash('success','تم التحديث','تم تغيير حالة العقد.');}
        catch(BusinessRuleException $exception){$this->flash('error','تعذر التحديث',$exception->getMessage());}
        return Response::redirect('/subcontracts/'.$id);
    }
    private function form(?int $id):Response
    {
        $canCreateVendor=$id===null&&$this->authorization->can('vendors.create');
        return Response::html($this->view->render('modules/subcontracts/form',['title'=>$id?'تعديل عقد مقاول باطن':'إضافة عقد مقاول باطن','subcontract'=>$id?$this->service->find($id):null,'canCreateVendor'=>$canCreateVendor,'workSections'=>$canCreateVendor?$this->vendors->workSections():[],...$this->service->refs()]));
    }
    private function save(Request $request,?int $id):Response
    {
        if(!$this->csrf->isValid($request->input('_token')))return Response::html('انتهت صلاحية الطلب.',419);
        try{$id=$this->service->save($this->validator->validate($request->all()),$id);return Response::redirect('/subcontracts/'.$id);}
        catch(ValidationException|BusinessRuleException $exception){
            if($exception instanceof ValidationException){$errors=$exception->errors;$field=array_key_first($errors);$message=(string)(($field!==null?($errors[$field][0]??null):null)??'يرجى مراجعة البيانات.');}
            else $message=$exception->getMessage();
            $this->flash('error','تعذر الحفظ',$message);
            return Response::redirect($id?'/subcontracts/'.$id.'/edit':'/subcontracts/create');
        }
    }
    private function flash(string $type,string $title,string $text):void{$this->session->flash('alert',compact('type','title','text'));}
}
