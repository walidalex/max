<?php
declare(strict_types=1);
namespace App\Modules\EmployeeCustodies\Controllers;
use App\Core\Exceptions\BusinessRuleException;
use App\Core\Exceptions\ValidationException;
use App\Core\Http\Csrf;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\Session;
use App\Core\View\View;
use App\Modules\EmployeeCustodies\Services\CustodyService;
use App\Modules\EmployeeCustodies\Validators\CustodyValidator;
use App\Modules\EmployeeCustodies\Validators\SettlementValidator;
final class CustodyController
{
 public function __construct(private readonly View$view,private readonly Csrf$csrf,private readonly Session$session,private readonly CustodyService$service,private readonly CustodyValidator$custodies,private readonly SettlementValidator$settlements){}
 public function index(Request$r):Response{return$this->page('modules/employee-custodies/index','عهد الموظفين',['custodies'=>$this->service->all()]);}
 public function create(Request$r):Response{return$this->form(null);}public function edit(Request$r):Response{return$this->form((int)$r->route('id'));}
 public function show(Request$r):Response{return$this->page('modules/employee-custodies/show','تفاصيل العهدة',[...$this->service->details((int)$r->route('id')),...$this->service->references()]);}
 public function print(Request$r):Response{return$this->page('modules/employee-custodies/print','طباعة العهدة',$this->service->details((int)$r->route('id')));}
 public function store(Request$r):Response{return$this->save($r,null);}public function update(Request$r):Response{return$this->save($r,(int)$r->route('id'));}
 public function issue(Request$r):Response{return$this->action($r,fn()=>$this->service->issue((int)$r->route('id')));}
 public function cancel(Request$r):Response{return$this->action($r,fn()=>$this->service->cancel((int)$r->route('id')));}
 public function expense(Request$r):Response{return$this->settle($r,'expense');}public function cashReturn(Request$r):Response{return$this->settle($r,'cash_return');}
 private function form(?int$id):Response{return$this->page('modules/employee-custodies/form',$id===null?'إنشاء عهدة':'تعديل العهدة',['custody'=>$id===null?null:$this->service->find($id),...$this->service->references()]);}
 private function save(Request$r,?int$id):Response{if(!$this->valid($r))return Response::html('انتهت صلاحية الطلب.',419);try{$id=$this->service->save($this->custodies->validate($r->all()),$id);$this->flash('success','تم الحفظ','تم حفظ مسودة العهدة.');return Response::redirect('/employee-custodies/'.$id);}catch(ValidationException|BusinessRuleException$e){$this->flash('error','تعذر الحفظ',$this->message($e));return Response::redirect($id===null?'/employee-custodies/create':'/employee-custodies/'.$id.'/edit');}}
 private function settle(Request$r,string$type):Response{return$this->action($r,fn()=>$this->service->settle((int)$r->route('id'),$this->settlements->validate($r->all(),$type)));}
 private function action(Request$r,callable$fn):Response{if(!$this->valid($r))return Response::html('انتهت صلاحية الطلب.',419);try{$fn();$this->flash('success','تم التنفيذ','تم تنفيذ العملية بنجاح.');}catch(ValidationException|BusinessRuleException$e){$this->flash('error','تعذر التنفيذ',$this->message($e));}return Response::redirect('/employee-custodies/'.(int)$r->route('id'));}
 private function page(string$template,string$title,array$data=[]):Response{return Response::html($this->view->render($template,['title'=>$title,...$data]));}private function valid(Request$r):bool{return$this->csrf->isValid($r->input('_token'));}private function message(\Throwable$e):string{return$e instanceof ValidationException?(string)reset($e->errors[array_key_first($e->errors)]):$e->getMessage();}private function flash(string$type,string$title,string$text):void{$this->session->flash('alert',compact('type','title','text'));}
}
