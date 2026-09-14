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
}
