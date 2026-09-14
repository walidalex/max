<?php
declare(strict_types=1);
namespace App\Modules\Projects\Controllers;
use App\Core\Exceptions\BusinessRuleException;use App\Core\Http\Request;use App\Core\Http\Response;use App\Modules\Projects\Services\ProjectService;
final class ProjectReferenceDataController
{
 public function __construct(private readonly ProjectService $projects){}
 public function contacts(Request $r):Response{try{return Response::json(['data'=>$this->projects->contacts((int)$r->input('client_id'))]);}catch(BusinessRuleException $e){return Response::json(['message'=>$e->getMessage()],422);}}
}
