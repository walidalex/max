<?php

declare(strict_types=1);

use App\Modules\Foundation\Controllers\FoundationController;
use App\Modules\Clients\Controllers\ClientDataTableController;
use App\Modules\Vendors\Controllers\VendorDataTableController;
use App\Modules\ProjectCosts\Controllers\ProjectCostDataTableController;
use App\Modules\Projects\Controllers\ProjectDataTableController;
use App\Modules\Projects\Controllers\ProjectReferenceDataController;
use App\Modules\ClientContracts\Controllers\ClientContractDataTableController;
use App\Modules\ClientContracts\Controllers\ClientContractReferenceDataController;
use App\Modules\ContractVariations\Controllers\ContractVariationDataTableController;
use App\Modules\SubcontractCertificates\Controllers\SubcontractCertificateDataTableController;
use App\Modules\SubcontractPayments\Controllers\SubcontractPaymentDataTableController;

$app->router()->get('/api/health', [FoundationController::class, 'health']);
$app->router()->get('/api/clients', [ClientDataTableController::class, 'index'], ['auth', 'permission:clients.view']);
$app->router()->get('/api/vendors', [VendorDataTableController::class, 'index'], ['auth', 'permission:vendors.view']);
$app->router()->get('/api/project-costs',[ProjectCostDataTableController::class,'global'],['auth','permission:project_costs.view']);
$app->router()->get('/api/projects/{project_id}/costs',[ProjectCostDataTableController::class,'project'],['auth','permission:project_costs.view']);
$app->router()->get('/api/projects', [ProjectDataTableController::class, 'index'], ['auth', 'permission:projects.view']);
$app->router()->get('/api/projects/client-contacts', [ProjectReferenceDataController::class, 'contacts'], ['auth', 'permission:projects.view']);
$app->router()->get('/api/contracts',[ClientContractDataTableController::class,'index'],['auth','permission:client_contracts.view']);
$app->router()->get('/api/contracts/client-contacts',[ClientContractReferenceDataController::class,'contacts'],['auth','permission:client_contracts.create']);
$app->router()->get('/api/contracts/{contract_id}/variations',[ContractVariationDataTableController::class,'index'],['auth','permission:contract_variations.view']);
$app->router()->get('/api/subcontracts/{subcontract_id}/certificates',[SubcontractCertificateDataTableController::class,'index'],['auth','permission:subcontract_certificates.view']);
$app->router()->get('/api/subcontracts/{subcontract_id}/payments',[SubcontractPaymentDataTableController::class,'index'],['auth','permission:subcontract_payments.view']);
