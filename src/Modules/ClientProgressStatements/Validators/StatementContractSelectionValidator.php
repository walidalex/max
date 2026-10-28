<?php

declare(strict_types=1);

namespace App\Modules\ClientProgressStatements\Validators;

use App\Core\Exceptions\ValidationException;
use App\Modules\ClientProgressStatements\DTOs\StatementContractSelectionData;

final class StatementContractSelectionValidator
{
    public function validate(array $input): StatementContractSelectionData
    {
        $errors = [];
        $ids = [];
        foreach (['project_id'=>'اختر المشروع.', 'contract_id'=>'اختر العقد.'] as $key => $message) {
            $id = filter_var($input[$key] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
            if ($id === false) $errors[$key][] = $message;
            $ids[$key] = $id;
        }
        if ($errors !== []) throw new ValidationException($errors);
        return new StatementContractSelectionData($ids['project_id'], $ids['contract_id']);
    }
}
