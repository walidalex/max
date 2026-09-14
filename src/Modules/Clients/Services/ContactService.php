<?php

declare(strict_types=1);

namespace App\Modules\Clients\Services;

use App\Core\Database\Database;
use App\Core\Exceptions\BusinessRuleException;
use App\Modules\Clients\DTOs\ContactData;
use App\Modules\Clients\Repositories\ContactRepository;
use mysqli_sql_exception;

final class ContactService
{
    public function __construct(
        private readonly ContactRepository $contacts,
        private readonly ClientService $clients,
        private readonly Database $database,
    ) {}

    /** @return list<array<string, mixed>> */
    public function forClient(int $clientId): array
    {
        $this->clients->find($clientId);
        return $this->contacts->forClient($clientId);
    }

    /** @return array<string, mixed> */
    public function find(int $id, int $clientId): array
    {
        return $this->contacts->findForClient($id, $clientId) ?? throw new BusinessRuleException('جهة الاتصال غير موجودة.');
    }

    public function save(int $clientId, ContactData $data, ?int $id = null): int
    {
        $this->clients->find($clientId);
        return $this->database->transaction(function () use ($clientId, $data, $id): int {
            if ($id !== null) {
                $this->find($id, $clientId);
            }
            if ($data->isPrimary) {
                $this->contacts->clearPrimary($clientId, $id);
            }
            if ($id === null) {
                return $this->contacts->create($clientId, $data);
            }
            $this->contacts->update($id, $clientId, $data);
            return $id;
        });
    }

    public function markPrimary(int $id, int $clientId): void
    {
        $this->find($id, $clientId);
        $this->database->transaction(function () use ($id, $clientId): void {
            $this->contacts->clearPrimary($clientId, $id);
            $this->contacts->setPrimary($id, $clientId);
        });
    }

    public function remove(int $id, int $clientId): void
    {
        $this->find($id, $clientId);
        try {
            $this->contacts->remove($id, $clientId);
        } catch (mysqli_sql_exception $exception) {
            if ($exception->getCode() === 1451) {
                throw new BusinessRuleException('لا يمكن حذف جهة الاتصال لأنها مستخدمة في بيانات أخرى.');
            }
            throw $exception;
        }
    }
}
