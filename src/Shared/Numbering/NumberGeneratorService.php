<?php

declare(strict_types=1);

namespace App\Shared\Numbering;

final class NumberGeneratorService
{
    public function __construct(private readonly NumberSequenceRepository $sequences) {}

    public function nextClientCode(): string
    {
        return sprintf('CL-%04d', $this->sequences->next('clients'));
    }

    public function nextVendorCode(): string
    {
        return sprintf('VN-%04d', $this->sequences->next('vendors'));
    }

    public function nextProjectCode(int $year): string
    {
        return sprintf('PRJ-%d-%04d', $year, $this->sequences->next('projects:' . $year));
    }

    public function nextClientContractCode(int $year): string
    {
        return sprintf('CTR-%d-%04d', $year, $this->sequences->next('client_contracts:' . $year));
    }

    public function nextContractVariationCode(int $year): string
    {
        return sprintf('VAR-%d-%04d', $year, $this->sequences->next('contract_variations:' . $year));
    }
    public function nextSubcontractCode(int $year): string { return sprintf('SUB-%d-%04d',$year,$this->sequences->next('subcontracts:'.$year)); }

    public function nextSubcontractProgressCertificateCode(int $year): string
    {
        return sprintf('SPC-%d-%04d', $year, $this->sequences->next('subcontract_progress_certificates:' . $year));
    }

    public function nextSubcontractPaymentCode(int $year): string
    {
        return sprintf('SPAY-%d-%04d', $year, $this->sequences->next('subcontract_payments:' . $year));
    }

    public function nextProjectActualCostCode(int $year): string
    {
        return sprintf('COST-%d-%04d', $year, $this->sequences->next('project_actual_costs:' . $year));
    }

    public function nextClientProgressStatementCode(int $year): string
    {
        return sprintf('CPS-%d-%04d', $year, $this->sequences->next('client_progress_statements:' . $year));
    }
}
