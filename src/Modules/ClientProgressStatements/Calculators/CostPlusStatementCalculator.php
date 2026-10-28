<?php

declare(strict_types=1);

namespace App\Modules\ClientProgressStatements\Calculators;

use App\Modules\ClientProgressStatements\Repositories\ClientProgressStatementRepository;

final class CostPlusStatementCalculator
{
    public function __construct(private readonly ClientProgressStatementRepository $statements) {}

    public function calculate(int $id,string $markup,?array $latest,string $subcontractCost='0.00'): array
    {
        $previous=[
            'cost'=>(string)($latest['cumulative_cost_amount']??'0.00'),
            'markup'=>(string)($latest['cumulative_markup_amount']??'0.00'),
            'variation'=>(string)($latest['cumulative_variation_amount']??'0.00'),
            'statement'=>(string)($latest['cumulative_statement_amount']??'0.00'),
        ];
        $cost=$this->statements->decimalAdd($this->statements->currentCostTotal($id),$subcontractCost);
        return $this->statements->calculate($cost,$markup,$this->statements->currentVariationTotal($id),$previous);
    }
}
