<?php
declare(strict_types=1);
namespace App\Modules\ContractVariations\DTOs;
final readonly class ContractVariationData { public function __construct(public int $clientContractId,public ?string $variationNumber,public string $variationType,public string $title,public ?string $description,public string $variationDate,public ?string $effectiveDate,public ?string $amount,public string $amountEffect,public ?string $markupPercentage,public ?string $notes){} }
