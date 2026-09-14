<?php
declare(strict_types=1);
namespace App\Modules\Projects\Controllers;
use App\Core\Http\Request;use App\Core\Http\Response;use App\Modules\Projects\Services\ProjectService;use App\Modules\Projects\Validators\ProjectTableQueryValidator;
final class ProjectDataTableController
{
 public function __construct(private readonly ProjectService $projects,private readonly ProjectTableQueryValidator $validator){}
 public function index(Request $r):Response{return Response::json($this->projects->dataTable($this->validator->validate($r->all())));}
}
