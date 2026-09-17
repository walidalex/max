<?php

declare(strict_types=1);

namespace App\Modules\ClientReceipts\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Modules\ClientReceipts\Services\ClientReceiptService;
use App\Modules\ClientReceipts\Validators\ClientReceiptTableQueryValidator;

final class ClientReceiptDataTableController
{
    public function __construct(
        private readonly ClientReceiptService $service,
        private readonly ClientReceiptTableQueryValidator $validator,
    ) {}
    public function global(Request $request): Response
    {
        return Response::json(
            $this->service->dataTable(
                $this->validator->validate($request->all()),
            ),
        );
    }
    public function contract(Request $request): Response
    {
        return Response::json(
            $this->service->dataTable(
                $this->validator->validate(
                    $request->all(),
                    (int) $request->route("contract_id"),
                ),
            ),
        );
    }
}
