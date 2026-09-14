<?php
declare(strict_types=1);
namespace App\Modules\ContractVariations\Controllers;
use App\Core\Http\Request;use App\Core\Http\Response;use App\Modules\ContractVariations\Services\ContractVariationService;use App\Modules\ContractVariations\Validators\ContractVariationTableQueryValidator;
final class ContractVariationDataTableController { public function __construct(private readonly ContractVariationService $service,private readonly ContractVariationTableQueryValidator $validator){}public function index(Request $r):Response{$q=$this->validator->validate($r->all(),(int)$r->route('contract_id'));return Response::json($this->service->dataTable($q));} }
