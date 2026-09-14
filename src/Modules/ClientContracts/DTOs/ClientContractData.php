<?php
declare(strict_types=1);
namespace App\Modules\ClientContracts\DTOs;
final readonly class ClientContractData { public function __construct(public int $projectId,public int $clientId,public ?int $clientContactId,public string $pricingMethod,public string $contractDate,public ?string $startDate,public ?string $expectedEndDate,public ?string $contractValue,public ?string $markupPercentage,public ?string $contractNumber,public string $title,public ?string $description,public ?string $terms,public ?string $notes){} }
