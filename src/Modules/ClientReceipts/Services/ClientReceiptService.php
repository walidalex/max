<?php

declare(strict_types=1);

namespace App\Modules\ClientReceipts\Services;

use App\Core\Auth\Auth;
use App\Core\Database\Database;
use App\Core\Exceptions\BusinessRuleException;
use App\Core\Support\Logger;
use App\Modules\ClientReceipts\DTOs\ClientReceiptData;
use App\Modules\ClientReceipts\DTOs\ClientReceiptTableQuery;
use App\Modules\ClientReceipts\Repositories\ClientReceiptRepository;
use App\Shared\Numbering\NumberGeneratorService;

final class ClientReceiptService
{
    private const ELIGIBLE_CONTRACT_STATUSES = [
        "active",
        "suspended",
        "completed",
    ];

    public function __construct(
        private readonly ClientReceiptRepository $receipts,
        private readonly NumberGeneratorService $numbers,
        private readonly Database $db,
        private readonly Auth $auth,
        private readonly Logger $logger,
    ) {}

    public function contract(int $id): array
    {
        return $this->receipts->contract($id) ??
            throw new BusinessRuleException("عقد العميل غير موجود.");
    }

    public function find(int $id): array
    {
        return $this->receipts->find($id) ??
            throw new BusinessRuleException("سند القبض غير موجود.");
    }

    public function save(ClientReceiptData $data, ?int $id = null): int
    {
        return $this->db->transaction(function () use ($data, $id): int {
            $contract =
                $this->receipts->contract($data->contractId, true) ??
                throw new BusinessRuleException("عقد العميل غير موجود.");
            if (
                !in_array(
                    $contract["status"],
                    self::ELIGIBLE_CONTRACT_STATUSES,
                    true,
                )
            ) {
                throw new BusinessRuleException(
                    "حالة العقد لا تسمح بإنشاء أو تعديل سند قبض.",
                );
            }
            $existing = $id === null ? null : $this->receipts->find($id, true);
            if (
                $id !== null &&
                ($existing === null ||
                    (int) $existing["client_contract_id"] !==
                        $data->contractId ||
                    $existing["status"] !== "draft")
            ) {
                throw new BusinessRuleException("سند القبض غير قابل للتعديل.");
            }
            if (
                $existing !== null &&
                preg_match(
                    "/^CRCT-(\d{4})-/",
                    (string) $existing["receipt_code"],
                    $matches,
                ) &&
                $matches[1] !== substr($data->receiptDate, 0, 4)
            ) {
                throw new BusinessRuleException(
                    "لا يمكن تغيير سنة تاريخ سند القبض. ألغِ المسودة وأنشئ سنداً جديداً للسنة المطلوبة.",
                );
            }
            if (
                $data->receiptNumber !== null &&
                $this->receipts->receiptNumberExists(
                    $data->contractId,
                    $data->receiptNumber,
                    $id,
                )
            ) {
                throw new BusinessRuleException(
                    "رقم سند القبض مستخدم بالفعل لهذا العقد.",
                );
            }
            if (!$this->receipts->amountIsPositive($data->amount)) {
                throw new BusinessRuleException(
                    "مبلغ سند القبض يجب أن يكون أكبر من صفر.",
                );
            }
            if ($id === null) {
                return $this->receipts->create(
                    $this->numbers->nextClientReceiptCode(
                        (int) substr($data->receiptDate, 0, 4),
                    ),
                    $data,
                    $contract,
                    $this->user(),
                );
            }
            $this->receipts->update($id, $data);
            return $id;
        });
    }

    public function post(int $id): void
    {
        $this->db->transaction(function () use ($id): void {
            $probe = $this->find($id);
            $contract =
                $this->receipts->contract(
                    (int) $probe["client_contract_id"],
                    true,
                ) ?? throw new BusinessRuleException("عقد العميل غير موجود.");
            $receipt =
                $this->receipts->find($id, true) ??
                throw new BusinessRuleException("سند القبض غير موجود.");
            if (
                (int) $receipt["client_contract_id"] !==
                    (int) $contract["id"] ||
                $receipt["status"] !== "draft"
            ) {
                throw new BusinessRuleException(
                    "يمكن ترحيل مسودة سند القبض فقط.",
                );
            }
            if (
                !in_array(
                    $contract["status"],
                    self::ELIGIBLE_CONTRACT_STATUSES,
                    true,
                )
            ) {
                throw new BusinessRuleException(
                    "حالة العقد لا تسمح بترحيل سند قبض.",
                );
            }
            if (
                !$this->receipts->amountIsPositive((string) $receipt["amount"])
            ) {
                throw new BusinessRuleException(
                    "مبلغ سند القبض يجب أن يكون أكبر من صفر.",
                );
            }
            if (
                !preg_match(
                    "/^CRCT-(\d{4})-/",
                    (string) $receipt["receipt_code"],
                    $matches,
                ) ||
                $matches[1] !== substr((string) $receipt["receipt_date"], 0, 4)
            ) {
                throw new BusinessRuleException(
                    "سنة كود سند القبض لا تطابق سنة تاريخ السند.",
                );
            }
            $this->receipts->post($id, $this->user(), $contract);
        });
    }

    public function cancel(int $id): void
    {
        $this->db->transaction(function () use ($id): void {
            $probe = $this->find($id);
            $this->receipts->contract((int) $probe["client_contract_id"], true);
            $receipt =
                $this->receipts->find($id, true) ??
                throw new BusinessRuleException("سند القبض غير موجود.");
            if ($receipt["status"] !== "draft") {
                throw new BusinessRuleException(
                    "يمكن إلغاء مسودة سند القبض فقط.",
                );
            }
            $this->receipts->cancel($id, $this->user());
        });
    }

    public function getPostedReceiptsTotal(int $contractId): string
    {
        return (string) $this->financialSummary($contractId)["posted_receipts"];
    }
    public function getAllocatedReceiptsTotal(int $contractId): string
    {
        return (string) $this->financialSummary(
            $contractId,
        )["allocated_receipts"];
    }
    public function getReceiptAllocatedTotal(int $receiptId): string
    {
        return (string) $this->receipts->receiptBalance(
            $receiptId,
        )["allocated"];
    }
    public function getReceiptUnallocatedAmount(int $receiptId): string
    {
        return (string) $this->receipts->receiptBalance(
            $receiptId,
        )["unallocated"];
    }
    public function getStatementAllocatedTotal(int $statementId): string
    {
        return (string) $this->receipts->statementBalance(
            $statementId,
        )["allocated"];
    }
    public function getStatementOutstandingAmount(int $statementId): string
    {
        return (string) $this->receipts->statementBalance(
            $statementId,
        )["outstanding"];
    }

    public function financialSummary(int $contractId): array
    {
        $this->contract($contractId);
        $totals = $this->receipts->financialSummary($contractId);
        $calculated = $this->receipts->calculateSummary(
            (string) $totals["approved_claims"],
            (string) $totals["posted_receipts"],
            (string) $totals["allocated_receipts"],
        );
        $inconsistent =
            (bool) $calculated["inconsistent"] ||
            (bool) $totals["cross_contract"] ||
            (bool) $totals["receipt_overallocated"] ||
            (bool) $totals["statement_overallocated"] ||
            (bool) $totals["invalid_status"];
        if ($inconsistent) {
            $this->logger->error("Client receipt settlement inconsistency", [
                "client_contract_id" => $contractId,
            ]);
        }
        return [
            "approved_claims" => (string) $totals["approved_claims"],
            "posted_receipts" => (string) $totals["posted_receipts"],
            "allocated_receipts" => (string) $totals["allocated_receipts"],
            "statement_outstanding" =>
                (string) $calculated["statement_outstanding"],
            "unallocated_receipts" =>
                (string) $calculated["unallocated_receipts"],
            "inconsistent" => $inconsistent,
        ];
    }

    public function dataTable(ClientReceiptTableQuery $query): array
    {
        if ($query->contractId !== null) {
            $this->contract($query->contractId);
        }
        $result = $this->receipts->dataTable($query);
        return [
            "draw" => $query->draw,
            "recordsTotal" => $result["recordsTotal"],
            "recordsFiltered" => $result["recordsFiltered"],
            "data" => $result["rows"],
        ];
    }

    public function references(): array
    {
        return $this->receipts->references();
    }
    private function user(): int
    {
        return $this->auth->id() ??
            throw new BusinessRuleException("تعذر تحديد المستخدم الحالي.");
    }
}
