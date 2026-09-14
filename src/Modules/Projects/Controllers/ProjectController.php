<?php
declare(strict_types=1);
namespace App\Modules\Projects\Controllers;
use App\Core\Exceptions\BusinessRuleException;use App\Core\Exceptions\ValidationException;use App\Core\Http\Csrf;use App\Core\Http\Request;use App\Core\Http\Response;use App\Core\Http\Session;use App\Core\View\View;use App\Modules\AccessControl\Services\AuthorizationService;use App\Modules\Projects\Services\ProjectService;use App\Modules\Projects\Validators\ProjectStatusValidator;use App\Modules\Projects\Validators\ProjectValidator;
final class ProjectController
{
 public function __construct(private readonly View $view,private readonly Csrf $csrf,private readonly Session $session,private readonly AuthorizationService $auth,private readonly ProjectService $projects,private readonly ProjectValidator $validator,private readonly ProjectStatusValidator $statusValidator){}
 public function index(Request $r):Response{$refs=$this->projects->referenceData();return Response::html($this->view->render('modules/projects/index',['title'=>'المشاريع',...$refs,'canCreate'=>$this->auth->can('projects.create'),'canEdit'=>$this->auth->can('projects.edit'),'canChangeStatus'=>$this->auth->can('projects.change_status')]));}
 public function create(Request $r):Response{return $this->form(null);}
 public function edit(Request $r):Response{return $this->form((int)$r->route('id'));}
 public function show(Request $r):Response{$p=$this->projects->find((int)$r->route('id'));return Response::html($this->view->render('modules/projects/show',['title'=>'تفاصيل المشروع','project'=>$p,'canEdit'=>$this->auth->can('projects.edit'),'canChangeStatus'=>$this->auth->can('projects.change_status'),'transitions'=>$this->projects->allowedTransitions((string)$p['status'])]));}
 public function store(Request $r):Response{return $this->save($r,null);}
 public function update(Request $r):Response{return $this->save($r,(int)$r->route('id'));}
 public function status(Request $r):Response
 {
  if(!$this->csrf->isValid($r->input('_token')))return Response::html('انتهت صلاحية الطلب.',419);
  $id=(int)$r->route('id');
  try{$status=$this->statusValidator->validate($r->input('status'));$this->projects->changeStatus($id,$status);$this->flash('success','تم التحديث','تم تغيير حالة المشروع.');}
  catch(ValidationException|BusinessRuleException $e){$message=$e instanceof ValidationException?(string)reset($e->errors['status']):$e->getMessage();$this->flash('error','تعذر التحديث',$message);}
  return Response::redirect("/projects/{$id}");
 }
 private function form(?int $id):Response{$p=$id===null?null:$this->projects->find($id);$refs=$this->projects->referenceData();$contacts=$p===null?[]:$this->projects->contacts((int)$p['client_id']);return Response::html($this->view->render('modules/projects/form',['title'=>$id===null?'إضافة مشروع':'تعديل المشروع','project'=>$p,'contacts'=>$contacts,...$refs]));}
 private function save(Request $r,?int $id):Response
 {
  if(!$this->csrf->isValid($r->input('_token')))return Response::html('انتهت صلاحية الطلب.',419);
  try{$input=$r->all();$input['status']=$id===null?'planning':(string)$this->projects->find($id)['status'];$id=$this->projects->save($this->validator->validate($input),$id);$this->flash('success','تم الحفظ','تم حفظ بيانات المشروع.');return Response::redirect("/projects/{$id}");}
  catch(ValidationException|BusinessRuleException $e){$message=$e instanceof ValidationException?(string)reset($e->errors[array_key_first($e->errors)]):$e->getMessage();$this->flash('error','تعذر الحفظ',$message);return Response::redirect($id===null?'/projects/create':"/projects/{$id}/edit");}
 }
 private function flash(string $type,string $title,string $text):void{$this->session->flash('alert',compact('type','title','text'));}
}
