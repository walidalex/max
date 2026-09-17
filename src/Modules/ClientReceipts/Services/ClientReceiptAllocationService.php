<?php

declare(strict_types=1);

namespace App\Modules\ClientReceipts\Services;

use App\Core\Auth\Auth;
use App\Core\Database\Database;
use App\Core\Exceptions\BusinessRuleException;
use App\Modules\ClientReceipts\DTOs\ClientReceiptAllocationData;
use App\Modules\ClientReceipts\Repositories\ClientReceiptRepository;

final class ClientReceiptAllocationService
{
    public function __construct(
        private readonly ClientReceiptRepository $receipts,
        private readonly ClientReceiptService $receiptService,
        private readonly Database $db,
        private readonly Auth $auth,
    ) {}

    public function eligibleStatements(int $receiptId): array
    {
        $receipt = $this->receiptService->find($receiptId);
        if ($receipt["status"] !== "posted") {
            return [];
        }
        return $this->receipts->eligibleStatements(
            (int) $receipt["client_contract_id"],
        );
    }

    public function allocationHistory(int $receiptId): array
    {
        $this->receiptService->find($receiptId);
        return $this->receipts->allocations($receiptId);
    }

    public function allocate(
        int $receiptId,
        ClientReceiptAllocationData $data,
    ): void {
        $this->db->transaction(function () use ($receiptId, $data): void {
            $probe = $this->receiptService->find($receiptId);
            $contractId = (int) $probe["client_contract_id"];
            $this->receipts->contract($contractId, true) ??
                throw new BusinessRuleException("عقد العميل غير موجود.");
            $receipt =
                $this->receipts->find($receiptId, true) ??
                throw new BusinessRuleException("سند القبض غير موجود.");
            if ($receipt["status"] !== "posted") {
                throw new BusinessRuleException(
                    "يمكن تخصيص سند قبض مرحّل فقط.",
                );
            }
            $summary = $this->receiptService->financialSummary($contractId);
            if ($summary["inconsistent"]) {
                throw new BusinessRuleException(
                    "يوجد عدم اتساق مالي في تسويات العقد. لا يمكن إضافة تخصيص جديد.",
                );
            }
            $receiptBalance = $this->receipts->receiptBalance($receiptId);
            if ((bool) $receiptBalance["inconsistent"]) {
                throw new BusinessRuleException(
                    "تخصيصات سند القبض تتجاوز مبلغ السند.",
                );
            }
            $requestedTotal = $this->receipts->sumDecimals(
                array_column($data->allocations, "allocated_amount"),
            );
            if (
                $this->receipts->exceeds(
                    $requestedTotal,
                    (string) $receiptBalance["unallocated"],
                )
            ) {
                throw new BusinessRuleException(
                    "إجمالي التخصيص يتجاوز الرصيد غير المخصص لسند القبض.",
                );
            }
            $ids = array_column($data->allocations, "statement_id");
            $statements = $this->receipts->lockStatements($ids);
            if (count($statements) !== count($ids)) {
                throw new BusinessRuleException(
                    "أحد المستخلصات المحددة غير موجود.",
                );
            }
            foreach ($data->allocations as $allocation) {
                $statement = $statements[$allocation["statement_id"]];
                if ($statement["status"] !== "approved") {
                    throw new BusinessRuleException(
                        "لا يمكن التخصيص إلا لمستخلص معتمد.",
                    );
                }
                if ((int) $statement["client_contract_id"] !== $contractId) {
                    throw new BusinessRuleException(
                        "لا يمكن تخصيص السند لمستخلص تابع لعقد آخر.",
                    );
                }
                if (
                    $this->receipts->exceeds(
                        $allocation["allocated_amount"],
                        (string) $statement["outstanding_amount"],
                    )
                ) {
                    throw new BusinessRuleException(
                        "مبلغ التخصيص يتجاوز الرصيد المستحق لأحد المستخلصات.",
                    );
                }
            }
            foreach ($data->allocations as $allocation) {
                $this->receipts->insertAllocation(
                    $receiptId,
                    $statements[$allocation["statement_id"]],
                    $allocation["allocated_amount"],
                    $allocation["notes"],
                    $this->user(),
                );
            }
        });
    }

    private function user(): int
    {
        return $this->auth->id() ??
            throw new BusinessRuleException("تعذر تحديد المستخدم الحالي.");
    }
}
