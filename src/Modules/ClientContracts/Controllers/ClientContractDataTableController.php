<?php
declare(strict_types=1);
namespace App\Modules\ClientContracts\Controllers;
use App\Core\Http\Request;use App\Core\Http\Response;use App\Modules\ClientContracts\Services\ClientContractService;use App\Modules\ClientContracts\Validators\ClientContractTableQueryValidator;
final class ClientContractDataTableController { public function __construct(private readonly ClientContractService $service,private readonly ClientContractTableQueryValidator $validator){}public function index(Request $r):Response{return Response::json($this->service->dataTable($this->validator->validate($r->all())));} }
