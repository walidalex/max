<?php
declare(strict_types=1);
namespace App\Modules\Vendors\Controllers;
use App\Core\Exceptions\BusinessRuleException;
use App\Core\Exceptions\ValidationException;
use App\Core\Http\Csrf;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\View\View;
use App\Modules\Vendors\Services\ContactService;
use App\Modules\Vendors\Validators\ContactValidator;
final class VendorContactController
{
    public function __construct(private readonly View $view,private readonly Csrf $csrf,private readonly ContactService $contacts,private readonly ContactValidator $validator) {}
    public function store(Request $request):Response{return $this->save($request,null);}
    public function update(Request $request):Response{return $this->save($request,(int)$request->route('contact_id'));}
    public function primary(Request $request):Response{return $this->action($request,fn(int $contactId,int $vendorId)=>$this->contacts->markPrimary($contactId,$vendorId),'تم تعيين جهة الاتصال الأساسية.');}
    public function remove(Request $request):Response{return $this->action($request,fn(int $contactId,int $vendorId)=>$this->contacts->remove($contactId,$vendorId),'تم حذف جهة الاتصال.');}
    private function save(Request $request,?int $contactId):Response
    {
        if(!$this->csrf->isValid($request->input('_token'))){return Response::json(['message'=>'انتهت صلاحية الطلب.'],419);}
        $vendorId=(int)$request->route('vendor_id');
        try{$this->contacts->save($vendorId,$this->validator->validate($request->all()),$contactId);return $this->contactsResponse($vendorId,'تم حفظ جهة الاتصال.');}
        catch(ValidationException $exception){return Response::json(['message'=>'يرجى مراجعة البيانات المدخلة.','errors'=>$exception->errors],422);}
        catch(BusinessRuleException $exception){return Response::json(['message'=>$exception->getMessage()],400);}
    }
    private function action(Request $request,callable $action,string $message):Response
    {
        if(!$this->csrf->isValid($request->input('_token'))){return Response::json(['message'=>'انتهت صلاحية الطلب.'],419);}
        $vendorId=(int)$request->route('vendor_id');
        try{$action((int)$request->route('contact_id'),$vendorId);return $this->contactsResponse($vendorId,$message);}
        catch(BusinessRuleException $exception){return Response::json(['message'=>$exception->getMessage()],400);}
    }
    private function contactsResponse(int $vendorId,string $message):Response
    {
        return Response::json(['message'=>$message,'html'=>$this->view->component('vendor-contacts',['vendorId'=>$vendorId,'contacts'=>$this->contacts->forVendor($vendorId),'canEdit'=>true,'csrfToken'=>$this->csrf->token()])]);
    }
}
