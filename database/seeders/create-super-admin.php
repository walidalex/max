<?php
declare(strict_types=1);

use App\Modules\AccessControl\DTOs\UserData;
use App\Modules\AccessControl\Repositories\UserRepository;
use App\Modules\AccessControl\Services\UserService;

$app=require dirname(__DIR__,2).'/bootstrap/app.php';
$username=(string)env('INITIAL_ADMIN_USERNAME','');
$name=(string)env('INITIAL_ADMIN_NAME','System Administrator');
$email=trim((string)env('INITIAL_ADMIN_EMAIL',''));
$password=(string)env('INITIAL_ADMIN_PASSWORD','');
if($username===''||$password===''){fwrite(STDERR,"Set INITIAL_ADMIN_USERNAME and INITIAL_ADMIN_PASSWORD for this command.\n");exit(1);}
if(strlen($password)<12){fwrite(STDERR,"INITIAL_ADMIN_PASSWORD must be at least 12 characters.\n");exit(1);}
/** @var UserRepository $repository */$repository=$app->make(UserRepository::class);
/** @var UserService $service */$service=$app->make(UserService::class);
$roleId=$repository->systemRoleId();
if($roleId===null){fwrite(STDERR,"Run migrations first.\n");exit(1);}
try{$id=$service->save(new UserData($username,$name,$email===''?null:$email,$password,[$roleId]));$service->assignRoles($id,[$roleId]);echo "Super administrator created with ID {$id}.\n";}
catch(Throwable $e){fwrite(STDERR,$e->getMessage().PHP_EOL);exit(1);}
