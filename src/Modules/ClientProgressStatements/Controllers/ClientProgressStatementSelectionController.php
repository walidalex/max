<?php declare(strict_types=1);
namespace App\Modules\ClientProgressStatements\Controllers;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Modules\ClientProgressStatements\Services\ClientProgressStatementService;
final class ClientProgressStatementSelectionController
{
    public function __construct(
        private readonly ClientProgressStatementService $service,
    ) {}
    public function costs(Request $r): Response
    {
        return Response::json([
            "data" => $this->service->getEligibleActualCosts(
                (int) $r->route("id"),
            ),
        ]);
    }
    public function variations(Request $r): Response
    {
        return Response::json([
            "data" => $this->service->getAvailableVariations(
                (int) $r->route("id"),
            ),
        ]);
    }
}
