<?php
declare(strict_types=1);
namespace App\Modules\Employees\Controllers;
use App\Core\Exceptions\BusinessRuleException;
use App\Core\Exceptions\ValidationException;
use App\Core\Http\Csrf;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\Session;
use App\Core\View\View;
use App\Modules\Employees\Services\EmployeeService;
use App\Modules\Employees\Validators\EmployeeValidator;
final class EmployeeController
{
 public function __construct(private readonly View$view,private readonly Csrf$csrf,private readonly Session$session,private readonly EmployeeService$service,private readonly EmployeeValidator$validator){}
 public function index(Request$r):Response{return Response::html($this->view->render('modules/employees/index',['title'=>'الموظفون','employees'=>$this->service->all()]));}
 public function store(Request$r):Response{return$this->save($r,null);}public function update(Request$r):Response{return$this->save($r,(int)$r->route('id'));}
 private function save(Request$r,?int$id):Response{if(!$this->csrf->isValid($r->input('_token')))return Response::html('انتهت صلاحية الطلب.',419);try{$this->service->save($this->validator->validate($r->all()),$id);$this->flash('success','تم الحفظ','تم حفظ بيانات الموظف.');}catch(ValidationException|BusinessRuleException$e){$message=$e instanceof ValidationException?(string)reset($e->errors[array_key_first($e->errors)]):$e->getMessage();$this->flash('error','تعذر الحفظ',$message);}return Response::redirect('/employees');}
 private function flash(string$type,string$title,string$text):void{$this->session->flash('alert',compact('type','title','text'));}
}
