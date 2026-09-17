<?php
declare(strict_types=1);
namespace App\Modules\SupplierInvoices\Controllers;
use App\Core\Exceptions\BusinessRuleException;
use App\Core\Exceptions\ValidationException;
use App\Core\Http\Csrf;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\Session;
use App\Core\View\View;
use App\Modules\AccessControl\Services\AuthorizationService;
use App\Modules\SupplierInvoices\Services\SupplierInvoiceService;
use App\Modules\SupplierInvoices\Validators\SupplierInvoiceValidator;
final class SupplierInvoiceController
{
    public function __construct(private readonly View $view,private readonly SupplierInvoiceService $service,private readonly SupplierInvoiceValidator $validator,private readonly Csrf $csrf,private readonly Session $session,private readonly AuthorizationService $authorization) {}
    public function index(Request $request):Response{return Response::html($this->view->render('modules/supplier-invoices/index',['title'=>'فواتير الموردين','references'=>$this->service->references(),'canCreate'=>$this->authorization->can('supplier_invoices.create')]));}
    public function create(Request $request):Response{$purchaseOrderId=filter_var($request->input('purchase_order_id'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);$seed=null;if($purchaseOrderId!==false){$purchaseOrder=$this->service->purchaseOrderPrefill((int)$purchaseOrderId);$seed=['purchase_order_id'=>$purchaseOrder['id'],'vendor_id'=>$purchaseOrder['vendor_id'],'project_id'=>$purchaseOrder['project_id']];}return $this->form($seed,[]);}
    public function store(Request $request):Response{return $this->save($request,null);}
    public function edit(Request $request):Response{$details=$this->service->details((int)$request->route('id'));if($details['invoice']['status']!=='draft')return Response::redirect('/supplier-invoices/'.$details['invoice']['id']);return $this->form($details['invoice'],$details['lines']);}
    public function update(Request $request):Response{return $this->save($request,(int)$request->route('id'));}
    public function show(Request $request):Response{$details=$this->service->details((int)$request->route('id'));return Response::html($this->view->render('modules/supplier-invoices/show',['title'=>'تفاصيل فاتورة المورد',...$details,'canEdit'=>$this->authorization->can('supplier_invoices.edit'),'canApprove'=>$this->authorization->can('supplier_invoices.approve'),'canCancel'=>$this->authorization->can('supplier_invoices.cancel')]));}
    public function print(Request $request):Response{$details=$this->service->details((int)$request->route('id'));if($details['invoice']['status']!=='approved')throw new BusinessRuleException('الطباعة متاحة للفواتير المعتمدة فقط.');return Response::html($this->view->render('modules/supplier-invoices/print',['title'=>'طباعة فاتورة مورد',...$details],'layouts/print'));}
    public function approve(Request $request):Response{return $this->action($request,'approve','تم اعتماد فاتورة المورد وإنشاء تكاليف المشروع.');}
    public function cancel(Request $request):Response{return $this->action($request,'cancel','تم إلغاء مسودة الفاتورة.');}
    private function form(?array $invoice,array $lines):Response{$editing=isset($invoice['id']);return Response::html($this->view->render('modules/supplier-invoices/form',['title'=>$editing?'تعديل فاتورة مورد':'إنشاء فاتورة مورد','invoice'=>$invoice,'lines'=>$lines,'references'=>$this->service->references()]));}
    private function save(Request $request,?int $id):Response{if(!$this->csrf->isValid($request->input('_token')))return Response::html('انتهت صلاحية الطلب.',419);try{$saved=$this->service->save($this->validator->validate($request->all()),$id);$this->flash('success','تم الحفظ','تم حفظ مسودة فاتورة المورد.');return Response::redirect('/supplier-invoices/'.$saved.'/edit');}catch(ValidationException|BusinessRuleException $exception){$message=$exception instanceof ValidationException?(string)reset($exception->errors[array_key_first($exception->errors)]):$exception->getMessage();$this->flash('error','تعذر الحفظ',$message);return Response::redirect($id?'/supplier-invoices/'.$id.'/edit':'/supplier-invoices/create');}}
    private function action(Request $request,string $action,string $message):Response{if(!$this->csrf->isValid($request->input('_token')))return Response::html('انتهت صلاحية الطلب.',419);$id=(int)$request->route('id');try{$this->service->{$action}($id);$this->flash('success','تم التحديث',$message);}catch(BusinessRuleException $exception){$this->flash('error','تعذر التحديث',$exception->getMessage());}return Response::redirect('/supplier-invoices/'.$id);}
    private function flash(string $type,string $title,string $text):void{$this->session->flash('alert',['type'=>$type,'title'=>$title,'text'=>$text]);}
}
