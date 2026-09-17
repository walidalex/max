<?php

declare(strict_types=1);

namespace App\Modules\SupplierPayments\Validators;

use App\Core\Exceptions\ValidationException;
use App\Modules\SupplierPayments\DTOs\SupplierPaymentData;

final class SupplierPaymentValidator
{
    public const METHODS = ['cash', 'bank_transfer', 'cheque', 'other'];

    public function validate(array $input): SupplierPaymentData
    {
        $errors = [];
        $invoiceId = max(0, (int) ($input['supplier_invoice_id'] ?? 0));
        if ($invoiceId < 1) $errors['supplier_invoice_id'][] = 'اختر فاتورة مورد معتمدة.';
        $date = trim((string) ($input['payment_date'] ?? ''));
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if (!$parsed || $parsed->format('Y-m-d') !== $date) $errors['payment_date'][] = 'تاريخ الدفع غير صالح.';
        $amount = trim((string) ($input['amount'] ?? ''));
        if (!preg_match('/^(?:0*[1-9]\d{0,15})(?:\.\d{1,2})?$|^0*\.\d{1,2}$/', $amount) || preg_match('/^0*(?:\.0{1,2})?$/', $amount)) {
            $errors['amount'][] = 'المبلغ يجب أن يكون أكبر من صفر، وبحد أقصى 16 رقمًا صحيحًا ودقتين عشريتين.';
        }
        $method = (string) ($input['payment_method'] ?? '');
        if (!in_array($method, self::METHODS, true)) $errors['payment_method'][] = 'طريقة الدفع غير صالحة.';
        $reference = $this->nullable($input['reference'] ?? null);
        if ($reference !== null && mb_strlen($reference) > 190) $errors['reference'][] = 'المرجع أطول من المسموح.';
        $notes = $this->nullable($input['notes'] ?? null);
        if ($notes !== null && mb_strlen($notes) > 5000) $errors['notes'][] = 'الملاحظات أطول من المسموح.';
        if ($errors !== []) throw new ValidationException($errors);
        [$whole, $fraction] = array_pad(explode('.', ltrim($amount, '0') ?: '0', 2), 2, '');
        return new SupplierPaymentData($invoiceId, $date, ($whole === '' ? '0' : $whole) . '.' . str_pad($fraction, 2, '0'), $method, $reference, $notes);
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }
}
