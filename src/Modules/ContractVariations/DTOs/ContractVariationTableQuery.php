<?php
declare(strict_types=1);
namespace App\Modules\ContractVariations\DTOs;
final readonly class ContractVariationTableQuery { public function __construct(public int $draw,public int $start,public int $length,public string $search,public string $sortColumn,public string $sortDirection,public int $contractId,public ?string $type,public ?string $status,public ?string $effect){} }
