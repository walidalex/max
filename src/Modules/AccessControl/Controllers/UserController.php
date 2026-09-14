<?php
declare(strict_types=1);
namespace App\Modules\AccessControl\Controllers;
use App\Core\Auth\Auth;
use App\Core\Exceptions\ValidationException;
use App\Core\Exceptions\BusinessRuleException;
use App\Core\Http\Csrf;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\Session;
use App\Core\View\View;
use App\Modules\AccessControl\Repositories\RoleRepository;
use App\Modules\AccessControl\Services\UserService;
use App\Modules\AccessControl\Validators\UserValidator;
final class UserController
{
    public function __construct(private readonly View $view,private readonly Csrf $csrf,private readonly Session $session,private readonly Auth $auth,private readonly UserService $users,private readonly RoleRepository $roles,private readonly UserValidator $validator) {}
    public function index(Request $r):Response{return Response::html($this->view->render('modules/access-control/users/index',['title'=>'المستخدمون','users'=>$this->users->all()]));}
    public function create(Request $r):Response{return $this->form(null);}
    public function edit(Request $r):Response{return $this->form((int)$r->route('id'));}
    private function form(?int $id):Response
    {
        $user=$id===null?null:$this->users->find($id);
        return Response::html($this->view->render('modules/access-control/users/form',['title'=>$id===null?'إضافة مستخدم':'تعديل مستخدم','user'=>$user]));
    }
    public function store(Request $r):Response{return $this->save($r,null);}
    public function update(Request $r):Response{return $this->save($r,(int)$r->route('id'));}
    private function save(Request $r,?int $id):Response
    {
        if(!$this->csrf->isValid($r->input('_token'))){return Response::html('انتهت صلاحية الطلب.',419);}
        try{$data=$this->validator->validate($r->all(),$id===null);$id=$this->users->save($data,$id);$this->session->flash('alert',['type'=>'success','title'=>'تم الحفظ','text'=>'تم حفظ بيانات المستخدم.']);return Response::redirect('/access/users');}
        catch(ValidationException|BusinessRuleException $e){$this->session->flash('alert',['type'=>'error','title'=>'تعذر الحفظ','text'=>$this->message($e)]);return Response::redirect($id===null?'/access/users/create':"/access/users/{$id}/edit");}
    }
    public function roles(Request $r):Response
    {
        $id=(int)$r->route('id');return Response::html($this->view->render('modules/access-control/users/roles',['title'=>'تعيين الأدوار','user'=>$this->users->find($id),'roles'=>$this->roles->all(),'selected'=>$this->users->roleIds($id)]));
    }
    public function assignRoles(Request $r):Response
    {
        $id=(int)$r->route('id');if(!$this->csrf->isValid($r->input('_token'))){return Response::html('انتهت صلاحية الطلب.',419);}
        try{$ids=array_values(array_unique(array_filter(array_map('intval',(array)$r->input('role_ids',[])))));$this->users->assignRoles($id,$ids);$this->session->flash('alert',['type'=>'success','title'=>'تم الحفظ','text'=>'تم تحديث أدوار المستخدم.']);}
        catch(BusinessRuleException $e){$this->session->flash('alert',['type'=>'error','title'=>'تعذر الحفظ','text'=>$e->getMessage()]);}
        return Response::redirect("/access/users/{$id}/roles");
    }
    public function activate(Request $r):Response
    {
        $id=(int)$r->route('id');if(!$this->csrf->isValid($r->input('_token'))){return Response::html('انتهت صلاحية الطلب.',419);}
        try{$this->users->setActive($id,(bool)(int)$r->input('active',0),(int)$this->auth->id());$this->session->flash('alert',['type'=>'success','title'=>'تم التحديث','text'=>'تم تحديث حالة المستخدم.']);}
        catch(BusinessRuleException $e){$this->session->flash('alert',['type'=>'error','title'=>'تعذر التحديث','text'=>$e->getMessage()]);}
        return Response::redirect('/access/users');
    }
    private function message(\Throwable $e):string{return $e instanceof ValidationException?(string)reset($e->errors[array_key_first($e->errors)]):$e->getMessage();}
}
