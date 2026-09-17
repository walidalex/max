<?php
declare(strict_types=1);namespace App\Modules\SupplierPayments\DTOs;final readonly class SupplierPaymentData{public function __construct(public int$supplierInvoiceId,public string$paymentDate,public string$amount,public string$paymentMethod,public?string$reference,public?string$notes){}}
