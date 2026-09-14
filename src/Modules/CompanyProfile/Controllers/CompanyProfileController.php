<?php
declare(strict_types=1);
namespace App\Modules\CompanyProfile\Controllers;
use App\Core\Exceptions\BusinessRuleException;
use App\Core\Exceptions\ValidationException;
use App\Core\Http\Csrf;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\Session;
use App\Core\View\View;
use App\Modules\CompanyProfile\Services\CompanyProfileService;
use App\Modules\CompanyProfile\Validators\CompanyProfileValidator;
use App\Modules\AccessControl\Services\AuthorizationService;
final class CompanyProfileController
{
    public function __construct(private readonly View $view,private readonly Csrf $csrf,private readonly Session $session,private readonly CompanyProfileService $company,private readonly CompanyProfileValidator $validator,private readonly AuthorizationService $authorization) {}
    public function show(Request $request):Response
    {
        return Response::html($this->view->render('modules/company-profile/show',['title'=>'ملف الشركة','company'=>$this->company->get(),'canEdit'=>$this->authorization->can('company_profile.edit')]));
    }
    public function update(Request $request):Response
    {
        if(!$this->csrf->isValid($request->input('_token'))){return Response::html('انتهت صلاحية الطلب.',419);}
        try{$data=$this->validator->validate($request->all(),$request->file('logo'));$this->company->update($data);$this->session->flash('alert',['type'=>'success','title'=>'تم الحفظ','text'=>'تم تحديث ملف الشركة.']);}
        catch(ValidationException|BusinessRuleException $e){$text=$e instanceof ValidationException?(string)reset($e->errors[array_key_first($e->errors)]):$e->getMessage();$this->session->flash('alert',['type'=>'error','title'=>'تعذر الحفظ','text'=>$text]);}
        return Response::redirect('/settings/company');
    }
    public function logo(Request $request):Response
    {
        try{$logo=$this->company->logo();return Response::file($logo['path'],$logo['mime']);}
        catch(BusinessRuleException){return Response::html('',404);}
    }
}
