<?php
declare(strict_types=1);
namespace App\Modules\AccessControl\Controllers;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\View\View;
use App\Modules\AccessControl\Services\PermissionService;
final class PermissionController
{
    public function __construct(private readonly View $view,private readonly PermissionService $permissions) {}
    public function index(Request $r):Response{return Response::html($this->view->render('modules/access-control/permissions/index',['title'=>'الصلاحيات','permissions'=>$this->permissions->all()]));}
}
