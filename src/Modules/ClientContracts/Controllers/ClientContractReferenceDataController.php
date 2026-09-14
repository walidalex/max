<?php
declare(strict_types=1);
namespace App\Modules\ClientContracts\Controllers;
use App\Core\Exceptions\BusinessRuleException;use App\Core\Http\Request;use App\Core\Http\Response;use App\Modules\ClientContracts\Services\ClientContractService;
final class ClientContractReferenceDataController { public function __construct(private readonly ClientContractService $service){}public function contacts(Request $r):Response{try{return Response::json(['data'=>$this->service->contacts((int)$r->input('project_id'))]);}catch(BusinessRuleException $e){return Response::json(['message'=>$e->getMessage()],404);}} }
