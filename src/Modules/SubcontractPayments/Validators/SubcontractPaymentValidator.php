<?php

declare(strict_types=1);

namespace App\Modules\SubcontractPayments\Validators;

use App\Core\Exceptions\ValidationException;
use App\Modules\SubcontractPayments\DTOs\SubcontractPaymentData;

final class SubcontractPaymentValidator
{
    public const METHODS = ['cash', 'bank_transfer', 'cheque', 'other'];

    public function validate(array $input, int $subcontractId): SubcontractPaymentData
    {
        $errors = [];
        $date = trim((string) ($input['payment_date'] ?? ''));
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if (!$parsed || $parsed->format('Y-m-d') !== $date) $errors['payment_date'][] = 'تاريخ الدفع غير صالح.';
        $amount = trim((string) ($input['amount'] ?? ''));
        if (!preg_match('/^(?:0*[1-9]\d*)(?:\.\d{1,2})?$|^0*\.\d{1,2}$/', $amount) || preg_match('/^0*(?:\.0{1,2})?$/', $amount)) $errors['amount'][] = 'مبلغ الدفعة يجب أن يكون أكبر من صفر وبدقتين عشريتين كحد أقصى.';
        $method = (string) ($input['payment_method'] ?? '');
        if (!in_array($method, self::METHODS, true)) $errors['payment_method'][] = 'طريقة الدفع غير صالحة.';
        $reference = $this->nullable($input['reference_number'] ?? null);
        if ($reference !== null && mb_strlen($reference) > 190) $errors['reference_number'][] = 'الرقم المرجعي أطول من المسموح.';
        $notes = $this->nullable($input['notes'] ?? null);
        if ($notes !== null && mb_strlen($notes) > 5000) $errors['notes'][] = 'الملاحظات أطول من المسموح.';
        if ($errors !== []) throw new ValidationException($errors);
        [$whole, $fraction] = array_pad(explode('.', ltrim($amount, '0') ?: '0', 2), 2, '');
        $normalized = ($whole === '' ? '0' : $whole) . '.' . str_pad($fraction, 2, '0');
        return new SubcontractPaymentData($subcontractId, $date, $normalized, $method, $reference, $notes);
    }

    private function nullable(mixed $value): ?string { $value = trim((string) $value); return $value === '' ? null : $value; }
}
