<?php
declare(strict_types=1);
namespace App\Modules\Accounting\Controllers;
use App\Core\Exceptions\BusinessRuleException;
use App\Core\Exceptions\ValidationException;
use App\Core\Http\Csrf;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\Session;
use App\Core\View\View;
use App\Modules\Accounting\Services\AccountingService;
use App\Modules\Accounting\Validators\AccountValidator;
use App\Modules\Accounting\Validators\JournalValidator;
final class AccountingController
{
 public function __construct(private readonly View$view,private readonly Csrf$csrf,private readonly Session$session,private readonly AccountingService$service,private readonly AccountValidator$accounts,private readonly JournalValidator$journals){}
 public function accounts(Request$r):Response{return$this->page('modules/accounting/accounts','دليل الحسابات',['accounts'=>$this->service->accounts()]);}
 public function saveAccount(Request$r):Response{return$this->action($r,function()use($r){$id=trim((string)$r->input('id'))===''?null:(int)$r->input('id');$this->service->saveAccount($this->accounts->validate($r->all()),$id);},'/accounting/accounts');}
 public function periods(Request$r):Response{return$this->page('modules/accounting/periods','الفترات المحاسبية',['periods'=>$this->service->periods()]);}
 public function createYear(Request$r):Response{return$this->action($r,fn()=>$this->service->createYear((int)$r->input('fiscal_year')),'/accounting/periods');}
 public function periodStatus(Request$r):Response{return$this->action($r,fn()=>$this->service->setPeriodStatus((int)$r->route('id'),(string)$r->input('status')),'/accounting/periods');}
 public function journalIndex(Request$r):Response{return$this->page('modules/accounting/journals/index','القيود اليومية',['journals'=>$this->service->journals()]);}
 public function journalCreate(Request$r):Response{return$this->journalForm(null);}
 public function journalEdit(Request$r):Response{return$this->journalForm((int)$r->route('id'));}
 public function journalShow(Request$r):Response{return$this->page('modules/accounting/journals/show','تفاصيل القيد',$this->service->journal((int)$r->route('id')));}
 public function journalStore(Request$r):Response{return$this->saveJournal($r,null);}
 public function journalUpdate(Request$r):Response{return$this->saveJournal($r,(int)$r->route('id'));}
 public function journalPost(Request$r):Response{return$this->action($r,fn()=>$this->service->postJournal((int)$r->route('id')),'/accounting/journals/'.(int)$r->route('id'));}
 public function setup(Request$r):Response{return$this->page('modules/accounting/setup','إعدادات الترحيل',['mappings'=>$this->service->setup(),'accounts'=>$this->service->references()['accounts'],'costCodeMappings'=>$this->service->costCodeMappings(),'expenseAccounts'=>$this->service->expenseAccounts()]);}
 public function saveSetup(Request$r):Response{return$this->action($r,fn()=>$this->service->saveMapping((string)$r->input('mapping_key'),(int)$r->input('account_id')),'/accounting/setup');}
 public function saveCostCodeMapping(Request$r):Response{return$this->action($r,fn()=>$this->service->saveCostCodeMapping((int)$r->input('cost_code_id'),(int)$r->input('account_id')),'/accounting/setup');}
 public function trialBalance(Request$r):Response{$from=(string)$r->input('date_from',date('Y-01-01'));$to=(string)$r->input('date_to',date('Y-m-d'));try{$data=$this->service->trialBalance($from,$to);}catch(BusinessRuleException){$data=['rows'=>[],'totals'=>[]];}return$this->page('modules/accounting/trial-balance','ميزان المراجعة',[...$data,'from'=>$from,'to'=>$to]);}
 private function journalForm(?int$id):Response{$data=$id===null?['journal'=>null,'lines'=>[]]:$this->service->journal($id);return$this->page('modules/accounting/journals/form',$id===null?'إنشاء قيد يومية':'تعديل قيد يومية',[...$data,...$this->service->references()]);}
 private function saveJournal(Request$r,?int$id):Response{if(!$this->csrf->isValid($r->input('_token')))return Response::html('انتهت صلاحية الطلب.',419);try{$id=$this->service->saveJournal($this->journals->validate($r->all()),$id);$this->flash('success','تم الحفظ','تم حفظ مسودة القيد.');return Response::redirect('/accounting/journals/'.$id);}catch(ValidationException|BusinessRuleException$e){$this->flash('error','تعذر الحفظ',$this->message($e));return Response::redirect($id===null?'/accounting/journals/create':'/accounting/journals/'.$id.'/edit');}}
 private function action(Request$r,callable$fn,string$url):Response{if(!$this->csrf->isValid($r->input('_token')))return Response::html('انتهت صلاحية الطلب.',419);try{$fn();$this->flash('success','تم التنفيذ','تم تنفيذ العملية بنجاح.');}catch(ValidationException|BusinessRuleException$e){$this->flash('error','تعذر التنفيذ',$this->message($e));}return Response::redirect($url);}
 private function page(string$template,string$title,array$data=[]):Response{return Response::html($this->view->render($template,['title'=>$title,...$data]));}
 private function message(\Throwable$e):string{return$e instanceof ValidationException?(string)reset($e->errors[array_key_first($e->errors)]):$e->getMessage();}
 private function flash(string$type,string$title,string$text):void{$this->session->flash('alert',compact('type','title','text'));}
}
