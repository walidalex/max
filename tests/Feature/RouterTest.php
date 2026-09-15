<?php

declare(strict_types=1);

use App\Core\Http\Request;

$response = $app->handle(new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/api/health', 'HTTP_ACCEPT' => 'application/json']));
if ($response->status() !== 200 || !str_contains($response->content(), '"status":"ok"')) {
    throw new RuntimeException('Health route did not return the expected response.');
}

$app->router()->get('/test/items/{id}', static fn (Request $request) => \App\Core\Http\Response::html((string) $request->route('id')));
$parameterResponse = $app->handle(new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/test/items/42']));
if ($parameterResponse->status() !== 200 || $parameterResponse->content() !== '42') {
    throw new RuntimeException('Route parameters were not resolved.');
}

/** @var \App\Core\Auth\Auth $auth */
$auth=$app->make(\App\Core\Auth\Auth::class);$auth->logout();
$protectedResponse=$app->handle(new Request([],[],['REQUEST_METHOD'=>'GET','REQUEST_URI'=>'/access/users']));
if($protectedResponse->status()!==302){throw new RuntimeException('Protected routes must redirect unauthenticated users.');}
$companyResponse=$app->handle(new Request([],[],['REQUEST_METHOD'=>'GET','REQUEST_URI'=>'/settings/company']));
if($companyResponse->status()!==302){throw new RuntimeException('Company settings route must require authentication.');}
$clientsResponse=$app->handle(new Request([],[],['REQUEST_METHOD'=>'GET','REQUEST_URI'=>'/clients']));
if($clientsResponse->status()!==302){throw new RuntimeException('Clients route must require authentication.');}
$clientsApiResponse=$app->handle(new Request([],[],['REQUEST_METHOD'=>'GET','REQUEST_URI'=>'/api/clients','HTTP_ACCEPT'=>'application/json']));
if($clientsApiResponse->status()!==401){throw new RuntimeException('Clients API must require authentication.');}
$vendorsResponse=$app->handle(new Request([],[],['REQUEST_METHOD'=>'GET','REQUEST_URI'=>'/vendors']));
if($vendorsResponse->status()!==302){throw new RuntimeException('Vendors route must require authentication.');}
$vendorsApiResponse=$app->handle(new Request([],[],['REQUEST_METHOD'=>'GET','REQUEST_URI'=>'/api/vendors','HTTP_ACCEPT'=>'application/json']));
if($vendorsApiResponse->status()!==401){throw new RuntimeException('Vendors API must require authentication.');}
$projectsResponse=$app->handle(new Request([],[],['REQUEST_METHOD'=>'GET','REQUEST_URI'=>'/projects']));
if($projectsResponse->status()!==302){throw new RuntimeException('Projects route must require authentication.');}
$projectsApiResponse=$app->handle(new Request([],[],['REQUEST_METHOD'=>'GET','REQUEST_URI'=>'/api/projects','HTTP_ACCEPT'=>'application/json']));
if($projectsApiResponse->status()!==401){throw new RuntimeException('Projects API must require authentication.');}
$database=$app->make(\App\Core\Database\Database::class);$adminResult=$database->execute("SELECT u.id,u.username,u.name,u.email FROM users u JOIN user_roles ur ON ur.user_id=u.id JOIN roles r ON r.id=ur.role_id WHERE r.code='super_admin' AND u.is_active=1 LIMIT 1");$admin=$adminResult instanceof mysqli_result?$adminResult->fetch_assoc():null;if($admin){$auth->login($admin);$csrfResponse=$app->handle(new Request([],['_token'=>'invalid'],['REQUEST_METHOD'=>'POST','REQUEST_URI'=>'/client-progress-statements/1/approve']));if($csrfResponse->status()!==419)throw new RuntimeException('Client progress statement mutation must enforce CSRF, got '.$csrfResponse->status());$auth->logout();}
$projectCostsResponse=$app->handle(new Request([],[],['REQUEST_METHOD'=>'GET','REQUEST_URI'=>'/project-costs']));
if($projectCostsResponse->status()!==302){throw new RuntimeException('Project costs route must require authentication.');}
$projectCostsApiResponse=$app->handle(new Request([],[],['REQUEST_METHOD'=>'GET','REQUEST_URI'=>'/api/project-costs','HTTP_ACCEPT'=>'application/json']));
if($projectCostsApiResponse->status()!==401){throw new RuntimeException('Project costs API must require authentication.');}
$clientStatementsResponse=$app->handle(new Request([],[],['REQUEST_METHOD'=>'GET','REQUEST_URI'=>'/client-progress-statements']));
if($clientStatementsResponse->status()!==302){throw new RuntimeException('Client progress statements route must require authentication.');}
$clientStatementsApiResponse=$app->handle(new Request([],[],['REQUEST_METHOD'=>'GET','REQUEST_URI'=>'/api/client-progress-statements','HTTP_ACCEPT'=>'application/json']));
if($clientStatementsApiResponse->status()!==401){throw new RuntimeException('Client progress statements API must require authentication.');}
$projectsResponse=$app->handle(new Request([],[],['REQUEST_METHOD'=>'GET','REQUEST_URI'=>'/projects']));
if($projectsResponse->status()!==302){throw new RuntimeException('Projects route must require authentication.');}
$projectsApiResponse=$app->handle(new Request([],[],['REQUEST_METHOD'=>'GET','REQUEST_URI'=>'/api/projects','HTTP_ACCEPT'=>'application/json']));
if($projectsApiResponse->status()!==401){throw new RuntimeException('Projects API must require authentication.');}
