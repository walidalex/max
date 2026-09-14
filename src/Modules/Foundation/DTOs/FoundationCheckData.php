<?php

declare(strict_types=1);

namespace App\Modules\Foundation\DTOs;

final readonly class FoundationCheckData
{
    public function __construct(public string $label) {}
}
