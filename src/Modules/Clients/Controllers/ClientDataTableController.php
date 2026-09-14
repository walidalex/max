<?php

declare(strict_types=1);

namespace App\Modules\Clients\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Modules\Clients\Services\ClientService;
use App\Modules\Clients\Validators\ClientTableQueryValidator;

final class ClientDataTableController
{
    public function __construct(
        private readonly ClientService $clients,
        private readonly ClientTableQueryValidator $validator,
    ) {}

    public function index(Request $request): Response
    {
        return Response::json($this->clients->dataTable($this->validator->validate($request->all())));
    }
}
