<?php

declare(strict_types=1);

namespace App\Modules\Vendors\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Modules\Vendors\Services\VendorService;
use App\Modules\Vendors\Validators\VendorTableQueryValidator;

final class VendorDataTableController
{
    public function __construct(
        private readonly VendorService $vendors,
        private readonly VendorTableQueryValidator $validator,
    ) {}

    public function index(Request $request): Response
    {
        return Response::json($this->vendors->dataTable($this->validator->validate($request->all())));
    }
}
