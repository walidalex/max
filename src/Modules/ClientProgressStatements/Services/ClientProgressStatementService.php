<?php
declare(strict_types=1);
namespace App\Modules\ClientProgressStatements\Services;
use App\Core\Auth\Auth;
use App\Core\Database\Database;
use App\Core\Exceptions\BusinessRuleException;
use App\Modules\ClientProgressStatements\Calculators\CostPlusStatementCalculator;
use App\Modules\ClientProgressStatements\DTOs\ClientProgressStatementData;
use App\Modules\ClientProgressStatements\DTOs\ClientProgressStatementTableQuery;
use App\Modules\ClientProgressStatements\Repositories\ClientProgressStatementRepository;
use App\Shared\Numbering\NumberGeneratorService;
final class ClientProgressStatementService
{
    private const ELIGIBLE_CONTRACT_STATUSES = [
        "active",
        "suspended",
        "completed",
    ];
    public function __construct(
        private readonly ClientProgressStatementRepository $statements,
        private readonly CostPlusStatementCalculator $calculator,
        private readonly NumberGeneratorService $numbers,
        private readonly Database $db,
        private readonly Auth $auth,
    ) {}
    public function contract(int $id): array
    {
        return $this->statements->contract($id) ??
            throw new BusinessRuleException("عقد العميل غير موجود.");
    }
    public function find(int $id): array
    {
        return $this->statements->find($id) ??
            throw new BusinessRuleException("مستخلص العميل غير موجود.");
    }
    public function save(
        int $contractId,
        ClientProgressStatementData $d,
        ?int $id = null,
    ): int {
        return $this->db->transaction(function () use ($contractId, $d, $id) {
            $contract =
                $this->statements->contract($contractId, true) ??
                throw new BusinessRuleException("عقد العميل غير موجود.");
            $this->assertContract($contract);
            $old = $id === null ? null : $this->statements->lockStatement($id);
            if (
                $id !== null &&
                ($old === null ||
                    (int) $old["client_contract_id"] !== $contractId)
            ) {
                throw new BusinessRuleException("المستخلص لا يتبع هذا العقد.");
            }
            if ($old !== null && $old["status"] !== "draft") {
                throw new BusinessRuleException(
                    "يمكن تعديل مسودة المستخلص فقط.",
                );
            }
            if ($old !== null) {
                $this->assertDraftSnapshots($old);
            }
            if (
                $old !== null &&
                $this->codeYear((string) $old["statement_code"]) !==
                    substr($d->statementDate, 0, 4)
            ) {
                throw new BusinessRuleException(
                    "لا يمكن تغيير سنة تاريخ المستخلص. ألغِ المسودة وأنشئ مستخلصًا جديدًا.",
                );
            }
            if (
                $d->statementNumber !== null &&
                $this->statements->numberExists(
                    $contractId,
                    $d->statementNumber,
                    $id,
                )
            ) {
                throw new BusinessRuleException(
                    "رقم المستخلص مستخدم بالفعل لهذا العقد.",
                );
            }
            if ($id === null) {
                $id = $this->statements->create(
                    $this->numbers->nextClientProgressStatementCode(
                        (int) substr($d->statementDate, 0, 4),
                    ),
                    $contractId,
                    (int) $contract["project_id"],
                    (int) $contract["client_id"],
                    $d,
                    $this->user(),
                );
            } else {
                $this->statements->updateDraft($id, $d);
            }
            $eligible = array_fill_keys(
                array_map(
                    "intval",
                    array_column($this->statements->eligibleCosts($id), "id"),
                ),
                true,
            );
            foreach ($d->costIds as $cost) {
                if (!isset($eligible[$cost])) {
                    throw new BusinessRuleException(
                        "إحدى التكاليف المختارة غير مؤهلة لهذا المستخلص.",
                    );
                }
            }
            $available = array_fill_keys(
                array_map(
                    "intval",
                    array_column(
                        $this->statements->availableVariations($id),
                        "id",
                    ),
                ),
                true,
            );
            foreach ($d->variations as $v) {
                if (!isset($available[$v["variation_id"]])) {
                    throw new BusinessRuleException(
                        "أحد التعديلات المختارة غير متاح للفوترة.",
                    );
                }
            }
            $this->statements->syncDraftDetails(
                $id,
                $d->costIds,
                $d->variations,
            );
            return $id;
        });
    }
    public function approve(int $id): void
    {
        $probe = $this->find($id);
        $this->db->transaction(function () use ($id, $probe) {
            $contract =
                $this->statements->contract(
                    (int) $probe["client_contract_id"],
                    true,
                ) ?? throw new BusinessRuleException("عقد العميل غير موجود.");
            $statement =
                $this->statements->lockStatement($id) ??
                throw new BusinessRuleException("مستخلص العميل غير موجود.");
            if ($statement["status"] !== "draft") {
                throw new BusinessRuleException(
                    "يمكن اعتماد مسودة المستخلص فقط.",
                );
            }
            $this->assertDraftSnapshots($statement);
            $this->assertContract($contract);
            $latest = $this->statements->latestApproved((int) $contract["id"]);
            if (
                $latest !== null &&
                (string) $statement["statement_date"] <
                    (string) $latest["statement_date"]
            ) {
                throw new BusinessRuleException(
                    "لا يمكن اعتماد مستخلص بتاريخ أقدم من آخر مستخلص معتمد للعقد.",
                );
            }
            $costDetails = $this->statements->lockCostDetails($id);
            $costs = $this->statements->lockSelectedCosts($id);
            if (count($costDetails) !== count($costs)) {
                throw new BusinessRuleException(
                    "تعذر قراءة جميع التكاليف المختارة.",
                );
            }
            if ($this->statements->conflictingCostIds($id) !== []) {
                throw new BusinessRuleException(
                    "تمت فوترة إحدى التكاليف المختارة في مستخلص آخر معتمد. حدّث المسودة وأعد المحاولة.",
                );
            }
            $costById = array_column($costs, null, "id");
            foreach ($costDetails as $d) {
                $x = $costById[$d["project_actual_cost_id"]] ?? null;
                if (
                    $x === null ||
                    $x["status"] !== "approved" ||
                    (int) $x["project_id"] !== (int) $contract["project_id"] ||
                    (string) $x["cost_date"] >
                        (string) $statement["statement_date"]
                ) {
                    throw new BusinessRuleException(
                        "إحدى التكاليف المختارة لم تعد مؤهلة للفوترة.",
                    );
                }
            }
            $variationDetails = $this->statements->lockVariationDetails($id);
            $variations = $this->statements->lockSelectedVariations($id);
            if (count($variationDetails) !== count($variations)) {
                throw new BusinessRuleException(
                    "تعذر قراءة جميع التعديلات المختارة.",
                );
            }
            $variationById = array_column($variations, null, "id");
            foreach ($variationDetails as $d) {
                $v = $variationById[$d["contract_variation_id"]] ?? null;
                if (
                    $v === null ||
                    $v["status"] !== "approved" ||
                    (int) $v["client_contract_id"] !== (int) $contract["id"] ||
                    !in_array(
                        $v["amount_effect"],
                        ["increase", "decrease"],
                        true,
                    )
                ) {
                    throw new BusinessRuleException(
                        "أحد التعديلات المختارة لم يعد مؤهلًا للفوترة.",
                    );
                }
                $used = $this->statements->decimalAdd(
                    (string) $v["previous_billed_amount"],
                    (string) $d["current_billed_amount"],
                );
                if (
                    $this->statements->decimalCompare(
                        $used,
                        (string) $v["amount"],
                    ) > 0
                ) {
                    throw new BusinessRuleException(
                        "مبلغ فوترة أحد التعديلات يتجاوز الرصيد المتبقي المعتمد.",
                    );
                }
            }
            $calculation = $this->calculator->calculate(
                $id,
                (string) $contract["markup_percentage"],
                $latest,
            );
            if (
                $this->statements->decimalCompare(
                    (string) $calculation["current_statement_amount"],
                    "0.00",
                ) <= 0
            ) {
                throw new BusinessRuleException(
                    "يجب أن تكون قيمة المستخلص الحالي أكبر من صفر. الخصومات الحالية تتجاوز قيمة المطالبة.",
                );
            }
            foreach ($costDetails as $d) {
                $this->statements->commitCost(
                    (int) $d["id"],
                    $costById[$d["project_actual_cost_id"]],
                );
            }
            foreach ($variationDetails as $d) {
                $v = $variationById[$d["contract_variation_id"]];
                $this->statements->commitVariation(
                    (int) $d["id"],
                    $v,
                    (string) $v["previous_billed_amount"],
                );
            }
            $this->statements->approve(
                $id,
                $this->statements->nextSequence((int) $contract["id"]),
                $this->user(),
                $calculation,
                $contract,
            );
            $verified = $this->statements->lockStatement($id);
            if (($verified["status"] ?? null) !== "approved") {
                throw new \RuntimeException(
                    "Statement approval update failed.",
                );
            }
            $this->assertApprovedSnapshots($verified);
        });
    }
    public function cancel(int $id): void
    {
        $probe = $this->find($id);
        $this->db->transaction(function () use ($id, $probe) {
            $this->statements->contract(
                (int) $probe["client_contract_id"],
                true,
            );
            $s =
                $this->statements->lockStatement($id) ??
                throw new BusinessRuleException("مستخلص العميل غير موجود.");
            if ($s["status"] !== "draft") {
                throw new BusinessRuleException(
                    "يمكن إلغاء مسودة المستخلص فقط.",
                );
            }
            $this->assertDraftSnapshots($s);
            $this->statements->cancel($id, $this->user());
            $cancelled = $this->statements->lockStatement($id);
            $this->assertCancelledSnapshots($cancelled ?? []);
        });
    }
    public function details(int $id): array
    {
        $s = $this->find($id);
        return [
            "statement" => $s,
            "costs" => $this->statements->costs($id),
            "variations" => $this->statements->variations($id),
        ];
    }
    public function getEligibleActualCostsForContract(
        int $id,
        string $date,
    ): array {
        $this->assertContract($this->contract($id));
        return $this->statements->eligibleCostsForContract($id, $date);
    }
    public function getAvailableVariationsForContract(int $id): array
    {
        $this->assertContract($this->contract($id));
        return $this->statements->availableVariationsForContract($id);
    }
    public function getEligibleActualCosts(int $id): array
    {
        $this->find($id);
        return $this->statements->eligibleCosts($id);
    }
    public function getAvailableVariations(int $id): array
    {
        $this->find($id);
        return $this->statements->availableVariations($id);
    }
    public function getApprovedStatementTotal(int $id): string
    {
        return $this->statements->approvedStatementTotal($id);
    }
    public function getApprovedClaimsTotal(int $id): string
    {
        $this->contract($id);
        return $this->statements->approvedClaimsTotal($id);
    }
    public function getPreviousApprovedTotals(int $id): array
    {
        $this->contract($id);
        $x = $this->statements->latestApproved($id);
        return [
            "cost" => (string) ($x["cumulative_cost_amount"] ?? "0.00"),
            "markup" => (string) ($x["cumulative_markup_amount"] ?? "0.00"),
            "variation" =>
                (string) ($x["cumulative_variation_amount"] ?? "0.00"),
            "statement" =>
                (string) ($x["cumulative_statement_amount"] ?? "0.00"),
        ];
    }
    public function references(): array
    {
        return $this->statements->references();
    }
    public function dataTable(ClientProgressStatementTableQuery $q): array
    {
        $x = $this->statements->dataTable($q);
        return [
            "draw" => $q->draw,
            "recordsTotal" => $x["recordsTotal"],
            "recordsFiltered" => $x["recordsFiltered"],
            "data" => $x["rows"],
        ];
    }
    private function assertDraftSnapshots(array $s): void
    {
        foreach (
            [
                "statement_sequence",
                "pricing_method_snapshot",
                "markup_percentage_snapshot",
                "previous_cost_amount",
                "current_cost_amount",
                "cumulative_cost_amount",
                "previous_markup_amount",
                "current_markup_amount",
                "cumulative_markup_amount",
                "previous_variation_amount",
                "current_variation_amount",
                "cumulative_variation_amount",
                "previous_statement_amount",
                "current_statement_amount",
                "cumulative_statement_amount",
                "approved_at",
                "approved_by",
                "cancelled_at",
                "cancelled_by",
            ]
            as $field
        ) {
            if ($s[$field] !== null) {
                throw new BusinessRuleException(
                    "بيانات المسودة المالية غير متسقة.",
                );
            }
        }
    }
    private function assertApprovedSnapshots(array $s): void
    {
        foreach (
            [
                "statement_sequence",
                "pricing_method_snapshot",
                "markup_percentage_snapshot",
                "client_name_snapshot",
                "project_code_snapshot",
                "project_name_snapshot",
                "contract_code_snapshot",
                "previous_cost_amount",
                "current_cost_amount",
                "cumulative_cost_amount",
                "previous_markup_amount",
                "current_markup_amount",
                "cumulative_markup_amount",
                "previous_variation_amount",
                "current_variation_amount",
                "cumulative_variation_amount",
                "previous_statement_amount",
                "current_statement_amount",
                "cumulative_statement_amount",
                "approved_at",
                "approved_by",
            ]
            as $field
        ) {
            if ($s[$field] === null) {
                throw new \RuntimeException(
                    "Approved statement snapshot is incomplete.",
                );
            }
        }
        if ($s["cancelled_at"] !== null || $s["cancelled_by"] !== null) {
            throw new \RuntimeException(
                "Approved statement audit is contradictory.",
            );
        }
    }
    private function assertCancelledSnapshots(array $s): void
    {
        if (
            ($s["status"] ?? null) !== "cancelled" ||
            ($s["cancelled_at"] ?? null) === null ||
            ($s["cancelled_by"] ?? null) === null
        ) {
            throw new \RuntimeException(
                "Cancelled statement audit is incomplete.",
            );
        }
        foreach (
            [
                "statement_sequence",
                "pricing_method_snapshot",
                "markup_percentage_snapshot",
                "previous_cost_amount",
                "current_cost_amount",
                "cumulative_cost_amount",
                "previous_markup_amount",
                "current_markup_amount",
                "cumulative_markup_amount",
                "previous_variation_amount",
                "current_variation_amount",
                "cumulative_variation_amount",
                "previous_statement_amount",
                "current_statement_amount",
                "cumulative_statement_amount",
                "approved_at",
                "approved_by",
            ]
            as $field
        ) {
            if (($s[$field] ?? null) !== null) {
                throw new \RuntimeException(
                    "Cancelled statement snapshot is contradictory.",
                );
            }
        }
    }
    private function assertContract(array $c): void
    {
        if ($c["pricing_method"] !== "cost_plus") {
            throw new BusinessRuleException(
                "المرحلة الحالية تدعم عقود Cost Plus فقط.",
            );
        }
        if (!in_array($c["status"], self::ELIGIBLE_CONTRACT_STATUSES, true)) {
            throw new BusinessRuleException(
                "حالة العقد لا تسمح بإنشاء أو اعتماد مستخلص عميل.",
            );
        }
        if ($c["markup_percentage"] === null) {
            throw new BusinessRuleException(
                "نسبة Cost Plus غير محددة في العقد.",
            );
        }
    }
    private function codeYear(string $code): string
    {
        return preg_match("/^CPS-(\d{4})-/", $code, $m) ? $m[1] : "";
    }
    private function user(): int
    {
        return $this->auth->id() ??
            throw new BusinessRuleException("تعذر تحديد المستخدم الحالي.");
    }
}
