<?php
declare(strict_types=1);
namespace App\Modules\Accounting\DTOs;
final readonly class JournalData
{
    /** @param list<array{account_id:int,description:string,debit:string,credit:string,project_id:?int,client_id:?int,vendor_id:?int,subcontract_id:?int,employee_id:?int,cost_code_id:?int,sort_order:int}> $lines */
    public function __construct(public string $date,public string $description,public ?string $reference,public array $lines) {}
}
