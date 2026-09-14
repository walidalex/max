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
use App\Modules\AccessControl\Repositories\PermissionRepository;
use App\Modules\AccessControl\Services\RoleService;
use App\Modules\AccessControl\Validators\RoleValidator;
final class RoleController
{
    public function __construct(private readonly View $view,private readonly Csrf $csrf,private readonly Session $session,private readonly RoleService $roles,private readonly PermissionRepository $permissions,private readonly RoleValidator $validator) {}
    public function index(Request $r):Response{return Response::html($this->view->render('modules/access-control/roles/index',['title'=>'الأدوار','roles'=>$this->roles->all()]));}
    public function create(Request $r):Response{return $this->form(null);}
    public function edit(Request $r):Response{return $this->form((int)$r->route('id'));}
    private function form(?int $id):Response
    {
        $role=$id===null?null:$this->roles->find($id);
        return Response::html($this->view->render('modules/access-control/roles/form',['title'=>$id===null?'إضافة دور':'تعديل دور','role'=>$role]));
    }
    public function store(Request $r):Response{return $this->save($r,null);}
    public function update(Request $r):Response{return $this->save($r,(int)$r->route('id'));}
    private function save(Request $r,?int $id):Response
    {
        if(!$this->csrf->isValid($r->input('_token'))){return Response::html('انتهت صلاحية الطلب.',419);}
        try{$this->roles->save($this->validator->validate($r->all()),$id);$this->session->flash('alert',['type'=>'success','title'=>'تم الحفظ','text'=>'تم حفظ بيانات الدور.']);return Response::redirect('/access/roles');}
        catch(ValidationException|BusinessRuleException $e){$text=$e instanceof ValidationException?(string)reset($e->errors[array_key_first($e->errors)]):$e->getMessage();$this->session->flash('alert',['type'=>'error','title'=>'تعذر الحفظ','text'=>$text]);return Response::redirect($id===null?'/access/roles/create':"/access/roles/{$id}/edit");}
    }
    public function permissions(Request $r):Response
    {
        $id=(int)$r->route('id');return Response::html($this->view->render('modules/access-control/roles/permissions',['title'=>'تعيين الصلاحيات','role'=>$this->roles->find($id),'permissions'=>$this->permissions->all(),'selected'=>$this->roles->permissionIds($id)]));
    }
    public function assignPermissions(Request $r):Response
    {
        $id=(int)$r->route('id');if(!$this->csrf->isValid($r->input('_token'))){return Response::html('انتهت صلاحية الطلب.',419);}
        try{$ids=array_values(array_unique(array_filter(array_map('intval',(array)$r->input('permission_ids',[])))));$this->roles->assignPermissions($id,$ids);$this->session->flash('alert',['type'=>'success','title'=>'تم الحفظ','text'=>'تم تحديث صلاحيات الدور.']);}
        catch(BusinessRuleException $e){$this->session->flash('alert',['type'=>'error','title'=>'تعذر الحفظ','text'=>$e->getMessage()]);}
        return Response::redirect("/access/roles/{$id}/permissions");
    }
}
