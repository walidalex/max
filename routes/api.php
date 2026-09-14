<?php

declare(strict_types=1);

use App\Modules\Foundation\Controllers\FoundationController;
use App\Modules\Clients\Controllers\ClientDataTableController;
use App\Modules\Vendors\Controllers\VendorDataTableController;
use App\Modules\Projects\Controllers\ProjectDataTableController;
use App\Modules\Projects\Controllers\ProjectReferenceDataController;

$app->router()->get('/api/health', [FoundationController::class, 'health']);
$app->router()->get('/api/clients', [ClientDataTableController::class, 'index'], ['auth', 'permission:clients.view']);
$app->router()->get('/api/vendors', [VendorDataTableController::class, 'index'], ['auth', 'permission:vendors.view']);
$app->router()->get('/api/projects', [ProjectDataTableController::class, 'index'], ['auth', 'permission:projects.view']);
$app->router()->get('/api/projects/client-contacts', [ProjectReferenceDataController::class, 'contacts'], ['auth', 'permission:projects.view']);
