<?php
declare(strict_types=1);
namespace App\Modules\Vendors\Controllers;
use App\Core\Exceptions\BusinessRuleException;
use App\Core\Exceptions\ValidationException;
use App\Core\Http\Csrf;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\Session;
use App\Core\View\View;
use App\Modules\AccessControl\Services\AuthorizationService;
use App\Modules\Vendors\Services\ContactService;
use App\Modules\Vendors\Services\VendorService;
use App\Modules\Vendors\Validators\VendorValidator;
final class VendorController
{
    public function __construct(private readonly View $view,private readonly Csrf $csrf,private readonly Session $session,private readonly AuthorizationService $authorization,private readonly VendorService $vendors,private readonly ContactService $contacts,private readonly VendorValidator $validator) {}
    public function index(Request $request):Response{return Response::html($this->view->render('modules/vendors/index',['title'=>'الموردون ومقاولو الباطن','canCreate'=>$this->authorization->can('vendors.create'),'canEdit'=>$this->authorization->can('vendors.edit'),'canActivate'=>$this->authorization->can('vendors.activate')]));}
    public function create(Request $request):Response{return $this->form(null);}
    public function edit(Request $request):Response{return $this->form((int)$request->route('id'));}
    public function show(Request $request):Response
    {
        $id=(int)$request->route('id');
        return Response::html($this->view->render('modules/vendors/show',['title'=>'تفاصيل المورد','vendor'=>$this->vendors->find($id),'contacts'=>$this->contacts->forVendor($id),'canEdit'=>$this->authorization->can('vendors.edit'),'canActivate'=>$this->authorization->can('vendors.activate')]));
    }
    public function store(Request $request):Response{return $this->save($request,null);}
    public function update(Request $request):Response{return $this->save($request,(int)$request->route('id'));}
    public function activate(Request $request):Response
    {
        if(!$this->csrf->isValid($request->input('_token'))){return Response::html('انتهت صلاحية الطلب.',419);}
        $id=(int)$request->route('id');
        try{$this->vendors->setActive($id,(bool)(int)$request->input('active',0));$this->flash('success','تم التحديث','تم تحديث حالة المورد.');}
        catch(BusinessRuleException $exception){$this->flash('error','تعذر التحديث',$exception->getMessage());}
        return Response::redirect('/vendors');
    }
    private function form(?int $id):Response{return Response::html($this->view->render('modules/vendors/form',['title'=>$id===null?'إضافة مورد':'تعديل المورد','vendor'=>$id===null?null:$this->vendors->find($id)]));}
    private function save(Request $request,?int $id):Response
    {
        if(!$this->csrf->isValid($request->input('_token'))){return Response::html('انتهت صلاحية الطلب.',419);}
        try{$id=$this->vendors->save($this->validator->validate($request->all()),$id);$this->flash('success','تم الحفظ','تم حفظ بيانات المورد.');return Response::redirect("/vendors/{$id}");}
        catch(ValidationException|BusinessRuleException $exception){$message=$exception instanceof ValidationException?(string)reset($exception->errors[array_key_first($exception->errors)]):$exception->getMessage();$this->flash('error','تعذر الحفظ',$message);return Response::redirect($id===null?'/vendors/create':"/vendors/{$id}/edit");}
    }
    private function flash(string $type,string $title,string $text):void{$this->session->flash('alert',compact('type','title','text'));}
}
