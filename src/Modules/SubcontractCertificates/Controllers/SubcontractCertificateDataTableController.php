<?php

declare(strict_types=1);

namespace App\Modules\SubcontractCertificates\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Modules\SubcontractCertificates\Services\SubcontractCertificateService;
use App\Modules\SubcontractCertificates\Validators\SubcontractCertificateTableQueryValidator;

final class SubcontractCertificateDataTableController
{
    public function __construct(private readonly SubcontractCertificateService $service, private readonly SubcontractCertificateTableQueryValidator $validator) {}

    public function index(Request $request): Response
    {
        $query = $this->validator->validate($request->all(), (int) $request->route('subcontract_id'));
        return Response::json($this->service->dataTable($query));
    }
}
