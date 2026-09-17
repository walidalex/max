<?php

declare(strict_types=1);

namespace App\Modules\SubcontractCertificates\Validators;

use App\Core\Exceptions\ValidationException;
use App\Modules\SubcontractCertificates\DTOs\SubcontractCertificateData;

final class SubcontractCertificateValidator
{
    public function validate(array $input, int $subcontractId, string $pricingMethod): SubcontractCertificateData
    {
        $errors = [];
        $date = $this->date($input['certificate_date'] ?? null);
        $from = $this->date($input['period_from'] ?? null, true);
        $to = $this->date($input['period_to'] ?? null, true);
        if (!is_string($date)) $errors['certificate_date'][] = 'تاريخ المستخلص غير صالح.';
        if ($from === false) $errors['period_from'][] = 'تاريخ بداية الفترة غير صالح.';
        if ($to === false) $errors['period_to'][] = 'تاريخ نهاية الفترة غير صالح.';
        if (is_string($from) && is_string($to) && $to < $from) $errors['period_to'][] = 'نهاية الفترة يجب ألا تسبق بدايتها.';

        $number = $this->nullable($input['certificate_number'] ?? null);
        if ($number !== null && mb_strlen($number) > 100) $errors['certificate_number'][] = 'الرقم المرجعي أطول من المسموح.';
        $notes = $this->nullable($input['notes'] ?? null);
        if ($notes !== null && mb_strlen($notes) > 5000) $errors['notes'][] = 'الملاحظات أطول من المسموح.';

        $percentage = null;
        $quantities = [];
        if ($pricingMethod === 'lump_sum') {
            $percentage = $this->decimal($input['current_progress_percentage'] ?? null, 4);
            if ($percentage === null) $errors['current_progress_percentage'][] = 'نسبة التقدم الحالية مطلوبة وغير سالبة.';
            elseif ($this->exceedsOneHundred($percentage)) $errors['current_progress_percentage'][] = 'نسبة التقدم الحالية لا تتجاوز 100٪.';
        } else {
            foreach (($input['quantities'] ?? []) as $itemId => $value) {
                $id = filter_var($itemId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                $quantity = $this->decimal($value, 4);
                if ($id === false || $quantity === null) {
                    $errors['quantities'][] = 'إحدى كميات التنفيذ الحالية غير صالحة.';
                    continue;
                }
                $quantities[(int) $id] = $quantity;
            }
        }

        if ($errors !== []) throw new ValidationException($errors);
        return new SubcontractCertificateData(
            $subcontractId,
            $number,
            is_string($from) ? $from : null,
            is_string($to) ? $to : null,
            (string) $date,
            $percentage,
            $quantities,
            $notes,
        );
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    private function date(mixed $value, bool $nullable = false): string|false|null
    {
        $value = trim((string) $value);
        if ($value === '') return $nullable ? null : false;
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value ? $value : false;
    }

    private function decimal(mixed $value, int $scale): ?string
    {
        $value = trim((string) $value);
        if (!preg_match('/^(?:0|[1-9]\d*)(?:\.\d+)?$/', $value)) return null;
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        if (strlen($fraction) > $scale) return null;
        return $whole . '.' . str_pad($fraction, $scale, '0');
    }

    private function exceedsOneHundred(string $value): bool
    {
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        $whole = ltrim($whole, '0') ?: '0';
        return strlen($whole) > 3 || (strlen($whole) === 3 && strcmp($whole, '100') > 0) || ($whole === '100' && trim($fraction, '0') !== '');
    }
}
