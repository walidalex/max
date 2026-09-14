<?php

declare(strict_types=1);

namespace App\Modules\Vendors\Services;

use App\Core\Database\Database;
use App\Core\Exceptions\BusinessRuleException;
use App\Modules\Vendors\DTOs\VendorContactData;
use App\Modules\Vendors\Repositories\ContactRepository;
use mysqli_sql_exception;

final class ContactService
{
    public function __construct(
        private readonly ContactRepository $contacts,
        private readonly VendorService $vendors,
        private readonly Database $database,
    ) {}

    /** @return list<array<string, mixed>> */
    public function forVendor(int $vendorId): array
    {
        $this->vendors->find($vendorId);
        return $this->contacts->forVendor($vendorId);
    }

    /** @return array<string, mixed> */
    public function find(int $id, int $vendorId): array
    {
        return $this->contacts->findForVendor($id, $vendorId) ?? throw new BusinessRuleException('جهة الاتصال غير موجودة.');
    }

    public function save(int $vendorId, VendorContactData $data, ?int $id = null): int
    {
        $this->vendors->find($vendorId);
        return $this->database->transaction(function () use ($vendorId, $data, $id): int {
            if ($id !== null) {
                $this->find($id, $vendorId);
            }
            if ($data->isPrimary) {
                $this->contacts->clearPrimary($vendorId, $id);
            }
            if ($id === null) {
                return $this->contacts->create($vendorId, $data);
            }
            $this->contacts->update($id, $vendorId, $data);
            return $id;
        });
    }

    public function markPrimary(int $id, int $vendorId): void
    {
        $this->find($id, $vendorId);
        $this->database->transaction(function () use ($id, $vendorId): void {
            $this->contacts->clearPrimary($vendorId, $id);
            $this->contacts->setPrimary($id, $vendorId);
        });
    }

    public function remove(int $id, int $vendorId): void
    {
        $this->find($id, $vendorId);
        try {
            $this->contacts->remove($id, $vendorId);
        } catch (mysqli_sql_exception $exception) {
            if ($exception->getCode() === 1451) {
                throw new BusinessRuleException('لا يمكن حذف جهة الاتصال لأنها مستخدمة في بيانات أخرى.');
            }
            throw $exception;
        }
    }
}
