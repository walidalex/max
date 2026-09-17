<?php
declare(strict_types=1);
namespace App\Modules\PurchaseOrders\Controllers;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Modules\PurchaseOrders\Services\PurchaseOrderService;
use App\Modules\PurchaseOrders\Validators\PurchaseOrderTableQueryValidator;
final class PurchaseOrderDataTableController
{
    public function __construct(private readonly PurchaseOrderService $service,private readonly PurchaseOrderTableQueryValidator $validator) {}
    public function index(Request $request):Response{return Response::json($this->service->dataTable($this->validator->validate($request->all())));}
}
