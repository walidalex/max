<?php

declare(strict_types=1);

namespace App\Modules\SubcontractCertificates\Services;

use App\Core\Auth\Auth;
use App\Core\Database\Database;
use App\Core\Exceptions\BusinessRuleException;
use App\Modules\SubcontractCertificates\DTOs\SubcontractCertificateData;
use App\Modules\SubcontractCertificates\DTOs\SubcontractCertificateTableQuery;
use App\Modules\SubcontractCertificates\Repositories\SubcontractCertificateRepository;
use App\Shared\Numbering\NumberGeneratorService;

final class SubcontractCertificateService
{
    public function __construct(
        private readonly SubcontractCertificateRepository $certificates,
        private readonly NumberGeneratorService $numbers,
        private readonly Database $db,
        private readonly Auth $auth,
    ) {}

    public function subcontract(int $id): array
    {
        return $this->certificates->subcontract($id) ?? throw new BusinessRuleException('عقد مقاول الباطن غير موجود.');
    }

    public function find(int $id): array
    {
        return $this->certificates->find($id) ?? throw new BusinessRuleException('مستخلص الأعمال غير موجود.');
    }

    public function formData(int $subcontractId, ?int $certificateId = null): array
    {
        $subcontract = $this->subcontract($subcontractId);
        $certificate = $certificateId === null ? null : $this->find($certificateId);
        if ($certificate !== null && (int) $certificate['subcontract_id'] !== $subcontractId) throw new BusinessRuleException('المستخلص لا يتبع هذا العقد.');
        if ($certificateId === null && !in_array($subcontract['status'], ['active', 'suspended'], true)) throw new BusinessRuleException('لا يمكن إنشاء مستخلص لهذا العقد في حالته الحالية.');
        if ($certificate !== null && $certificate['status'] !== 'draft') throw new BusinessRuleException('المستخلص المعتمد أو الملغي غير قابل للتعديل.');
        $items = [];
        if ($subcontract['pricing_method'] === 'boq') {
            if ($subcontract['boq_status'] !== 'approved') throw new BusinessRuleException('يجب وجود BOQ معتمد للعقد.');
            $items = $certificateId === null
                ? $this->certificates->boqSourceItems((int) $subcontract['boq_id'], $subcontractId)
                : $this->certificates->items($certificateId);
        }
        return compact('subcontract', 'certificate', 'items');
    }

    public function save(SubcontractCertificateData $data, ?int $id = null): int
    {
        return $this->db->transaction(function () use ($data, $id): int {
            $subcontract = $this->certificates->subcontract($data->subcontractId, true) ?? throw new BusinessRuleException('عقد مقاول الباطن غير موجود.');
            $existing = $id === null ? null : $this->certificates->find($id, true);
            if ($id === null && !in_array($subcontract['status'], ['active', 'suspended'], true)) throw new BusinessRuleException('يمكن إنشاء المستخلص لعقد نشط أو معلق فقط.');
            if ($existing !== null && ((int) $existing['subcontract_id'] !== $data->subcontractId || $existing['status'] !== 'draft')) throw new BusinessRuleException('المستخلص غير قابل للتعديل.');
            if ($data->certificateNumber !== null && $this->certificates->numberExists($data->subcontractId, $data->certificateNumber, $id)) throw new BusinessRuleException('رقم المستخلص المرجعي مستخدم لهذا العقد.');
            if ($subcontract['pricing_method'] === 'boq' && $subcontract['boq_status'] !== 'approved') throw new BusinessRuleException('يجب وجود BOQ معتمد للعقد.');

            if ($id === null) {
                $id = $this->certificates->create($this->numbers->nextSubcontractProgressCertificateCode((int) substr($data->certificateDate, 0, 4)), $data);
                if ($subcontract['pricing_method'] === 'boq') $this->certificates->seedBoqItems($id, (int) $subcontract['boq_id']);
            } else {
                $this->certificates->updateDraft($id, $data);
            }
            if ($subcontract['pricing_method'] === 'boq') {
                if ($this->certificates->submittedItemCount($id, array_keys($data->quantities)) !== count($data->quantities)) throw new BusinessRuleException('أحد البنود لا يتبع BOQ هذا العقد.');
                $this->certificates->updateQuantities($id, $data->quantities);
            }
            return $id;
        });
    }

    public function approve(int $id): void
    {
        $this->db->transaction(function () use ($id): void {
            $certificate = $this->certificates->find($id) ?? throw new BusinessRuleException('مستخلص الأعمال غير موجود.');
            $subcontract = $this->certificates->subcontract((int) $certificate['subcontract_id'], true) ?? throw new BusinessRuleException('عقد مقاول الباطن غير موجود.');
            $certificate = $this->certificates->find($id, true) ?? throw new BusinessRuleException('مستخلص الأعمال غير موجود.');
            if ($certificate['status'] !== 'draft') throw new BusinessRuleException('يمكن اعتماد المسودة فقط.');
            if (!in_array($subcontract['status'], ['active', 'suspended'], true)) throw new BusinessRuleException('يجب أن يكون العقد نشطًا أو معلقًا عند الاعتماد.');
            $latest = $this->certificates->latestApprovedSequence((int) $subcontract['id']);
            if ($latest !== null && ($certificate['certificate_date'] < $latest['certificate_date'] || ($certificate['certificate_date'] === $latest['certificate_date'] && (int) $certificate['id'] < (int) $latest['id']))) throw new BusinessRuleException('يجب اعتماد المستخلصات بترتيب التاريخ ثم ترتيب إنشائها عند تساوي التاريخ.');

            $userId = $this->auth->id() ?? throw new BusinessRuleException('تعذر تحديد المستخدم الحالي.');
            if ($subcontract['pricing_method'] === 'boq') {
                if ($subcontract['boq_status'] !== 'approved') throw new BusinessRuleException('يجب بقاء BOQ في حالة معتمدة.');
                $items = $this->certificates->items($id);
                if ($items === []) throw new BusinessRuleException('لا توجد بنود قابلة للاعتماد.');
                $previous = $this->certificates->approvedQuantities((int) $subcontract['id']);
                foreach ($items as $item) {
                    $boqItemId = (int) $item['subcontract_boq_item_id'];
                    $this->certificates->finalizeBoqItem($id, $boqItemId, $previous[$boqItemId] ?? '0.0000');
                }
                if ($this->certificates->invalidBoqItemCount($id) > 0) throw new BusinessRuleException('الكمية التراكمية تتجاوز الكمية التعاقدية لأحد البنود.');
                if ($this->certificates->positiveBoqItemCount($id) < 1) throw new BusinessRuleException('يجب إدخال كمية حالية موجبة لبند واحد على الأقل.');
                $this->certificates->approveBoq($id, $userId, $this->certificates->boqTotals($id));
                return;
            }

            $current = (string) $certificate['current_progress_percentage'];
            if (!$this->certificates->lumpProgressIsPositive($current)) throw new BusinessRuleException('يجب أن تكون نسبة التقدم الحالية أكبر من صفر.');
            $previous = $this->certificates->approvedLumpProgress((int) $subcontract['id']);
            if ($this->certificates->lumpProgressExceeds($previous, $current)) throw new BusinessRuleException('نسبة التقدم التراكمية تتجاوز 100٪.');
            $this->certificates->approveLump($id, $userId, $previous, (string) $subcontract['contract_value']);
        });
    }

    public function cancel(int $id): void
    {
        $this->db->transaction(function () use ($id): void {
            $certificate = $this->certificates->find($id) ?? throw new BusinessRuleException('مستخلص الأعمال غير موجود.');
            $this->certificates->subcontract((int) $certificate['subcontract_id'], true);
            $certificate = $this->certificates->find($id, true) ?? throw new BusinessRuleException('مستخلص الأعمال غير موجود.');
            if ($certificate['status'] !== 'draft') throw new BusinessRuleException('يمكن إلغاء مسودة المستخلص فقط.');
            $userId = $this->auth->id() ?? throw new BusinessRuleException('تعذر تحديد المستخدم الحالي.');
            $this->certificates->cancel($id, $userId);
        });
    }

    public function getApprovedEarnedValue(int $subcontractId): string
    {
        $this->subcontract($subcontractId);
        return $this->certificates->approvedEarnedValue($subcontractId);
    }

    public function getApprovedProgressPercentage(int $subcontractId): string
    {
        $this->subcontract($subcontractId);
        return $this->certificates->approvedProgressPercentage($subcontractId);
    }

    public function certificateItems(int $certificateId): array
    {
        $this->find($certificateId);
        return $this->certificates->items($certificateId);
    }

    public function dataTable(SubcontractCertificateTableQuery $query): array
    {
        $this->subcontract($query->subcontractId);
        $result = $this->certificates->dataTable($query);
        return ['draw' => $query->draw, 'recordsTotal' => $result['recordsTotal'], 'recordsFiltered' => $result['recordsFiltered'], 'data' => $result['rows']];
    }
}
