<?php
declare(strict_types=1);
namespace App\Modules\ClientProgressStatements\Calculators;
use App\Modules\ClientProgressStatements\Repositories\ClientProgressStatementRepository;
final class BoqStatementCalculator
{
    public function __construct(private readonly ClientProgressStatementRepository $statements) {}
    public function line(int $itemId,string $currentQuantity,string $contractQuantity,string $unitRate):array
    {
        return $this->statements->calculateBoqLine($itemId,$currentQuantity,$contractQuantity,$unitRate);
    }
    public function header(int $statementId,string $variation,?array $latest):array
    {
        return $this->statements->calculateBoqHeader($statementId,$variation,$latest);
    }
}
