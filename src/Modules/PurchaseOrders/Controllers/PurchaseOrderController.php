<?php
declare(strict_types=1);
namespace App\Modules\PurchaseOrders\Controllers;
use App\Core\Exceptions\BusinessRuleException;
use App\Core\Exceptions\ValidationException;
use App\Core\Http\Csrf;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\Session;
use App\Core\View\View;
use App\Modules\AccessControl\Services\AuthorizationService;
use App\Modules\PurchaseOrders\Services\PurchaseOrderService;
use App\Modules\PurchaseOrders\Validators\PurchaseOrderValidator;
final class PurchaseOrderController
{
    public function __construct(private readonly View $view,private readonly PurchaseOrderService $service,private readonly PurchaseOrderValidator $validator,private readonly Csrf $csrf,private readonly Session $session,private readonly AuthorizationService $authorization) {}
    public function index(Request $request):Response{return Response::html($this->view->render('modules/purchase-orders/index',['title'=>'أوامر الشراء','references'=>$this->service->references(),'canCreate'=>$this->authorization->can('purchase_orders.create')]));}
    public function create(Request $request):Response{return $this->form(null,[]);}
    public function store(Request $request):Response{return $this->save($request,null);}
    public function edit(Request $request):Response{$details=$this->service->details((int)$request->route('id'));if($details['order']['status']!=='draft')return Response::redirect('/purchase-orders/'.$details['order']['id']);return $this->form($details['order'],$details['lines']);}
    public function update(Request $request):Response{return $this->save($request,(int)$request->route('id'));}
    public function show(Request $request):Response{$details=$this->service->details((int)$request->route('id'));return Response::html($this->view->render('modules/purchase-orders/show',['title'=>'تفاصيل أمر الشراء',...$details,'canEdit'=>$this->authorization->can('purchase_orders.edit'),'canApprove'=>$this->authorization->can('purchase_orders.approve'),'canCancel'=>$this->authorization->can('purchase_orders.cancel'),'canCreateSupplierInvoice'=>$this->authorization->can('supplier_invoices.create')]));}
    public function print(Request $request):Response{$details=$this->service->details((int)$request->route('id'));if($details['order']['status']!=='approved')throw new BusinessRuleException('الطباعة متاحة لأوامر الشراء المعتمدة فقط.');return Response::html($this->view->render('modules/purchase-orders/print',['title'=>'طباعة أمر شراء',...$details],'layouts/print'));}
    public function approve(Request $request):Response{return $this->action($request,'approve','تم اعتماد أمر الشراء كالتزام شراء فقط دون أثر مالي.');}
    public function cancel(Request $request):Response{return $this->action($request,'cancel','تم إلغاء مسودة أمر الشراء دون أثر مالي.');}
    private function form(?array $order,array $lines):Response{return Response::html($this->view->render('modules/purchase-orders/form',['title'=>$order?'تعديل مسودة أمر شراء':'إنشاء أمر شراء','order'=>$order,'lines'=>$lines,'references'=>$this->service->references()]));}
    private function save(Request $request,?int $id):Response{if(!$this->csrf->isValid($request->input('_token')))return Response::html('انتهت صلاحية الطلب.',419);try{$saved=$this->service->save($this->validator->validate($request->all()),$id);$this->flash('success','تم الحفظ','تم حفظ مسودة أمر الشراء.');return Response::redirect('/purchase-orders/'.$saved.'/edit');}catch(ValidationException|BusinessRuleException $exception){$message=$exception instanceof ValidationException?(string)reset($exception->errors[array_key_first($exception->errors)]):$exception->getMessage();$this->flash('error','تعذر الحفظ',$message);return Response::redirect($id?'/purchase-orders/'.$id.'/edit':'/purchase-orders/create');}}
    private function action(Request $request,string $action,string $message):Response{if(!$this->csrf->isValid($request->input('_token')))return Response::html('انتهت صلاحية الطلب.',419);$id=(int)$request->route('id');try{$this->service->{$action}($id);$this->flash('success','تم التحديث',$message);}catch(BusinessRuleException $exception){$this->flash('error','تعذر التحديث',$exception->getMessage());}return Response::redirect('/purchase-orders/'.$id);}
    private function flash(string $type,string $title,string $text):void{$this->session->flash('alert',['type'=>$type,'title'=>$title,'text'=>$text]);}
}
