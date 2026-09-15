<?php
declare(strict_types=1);
namespace App\Modules\AccessControl\Controllers;
use App\Core\Exceptions\ValidationException;
use App\Core\Exceptions\BusinessRuleException;
use App\Core\Http\Csrf;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\Session;
use App\Core\View\View;
use App\Modules\AccessControl\Services\AuthenticationService;
use App\Modules\AccessControl\Validators\LoginValidator;
use App\Modules\CompanyProfile\Services\CompanyProfileService;
final class AuthenticationController
{
    public function __construct(private readonly View $view,private readonly Csrf $csrf,private readonly Session $session,private readonly LoginValidator $validator,private readonly AuthenticationService $auth,private readonly CompanyProfileService $company) {}
    public function form(Request $request):Response
    {
        try{$company=$this->company->get();$logo=$this->company->logoDataUri();}
        catch(BusinessRuleException){$company=[];$logo=null;}
        return Response::html($this->view->render('modules/access-control/login',['title'=>'تسجيل الدخول','companyBrand'=>$company,'companyLogoDataUri'=>$logo],'layouts/auth'));
    }
    public function login(Request $request):Response
    {
        if(!$this->csrf->isValid($request->input('_token'))){return Response::html('انتهت صلاحية الطلب.',419);}
        try{$data=$this->validator->validate($request->all());if(!$this->auth->attempt($data)){throw new BusinessRuleException('بيانات الدخول غير صحيحة أو الحساب غير نشط.');}}
        catch(ValidationException|BusinessRuleException $e){$this->session->flash('alert',['type'=>'error','title'=>'تعذر تسجيل الدخول','text'=>$e->getMessage()]);return Response::redirect('/login');}
        return Response::redirect('/');
    }
    public function logout(Request $request):Response
    {
        if(!$this->csrf->isValid($request->input('_token'))){return Response::html('انتهت صلاحية الطلب.',419);}
        $this->auth->logout();return Response::redirect('/login');
    }
}
