<?php

declare(strict_types=1);

namespace App\Modules\ClientReceipts\Controllers;

use App\Core\Exceptions\BusinessRuleException;
use App\Core\Exceptions\ValidationException;
use App\Core\Http\Csrf;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\Session;
use App\Core\View\View;
use App\Modules\AccessControl\Services\AuthorizationService;
use App\Modules\ClientReceipts\Services\ClientReceiptAllocationService;
use App\Modules\ClientReceipts\Services\ClientReceiptService;
use App\Modules\ClientReceipts\Validators\ClientReceiptValidator;

final class ClientReceiptController
{
    public function __construct(
        private readonly View $view,
        private readonly Csrf $csrf,
        private readonly Session $session,
        private readonly AuthorizationService $auth,
        private readonly ClientReceiptService $service,
        private readonly ClientReceiptAllocationService $allocations,
        private readonly ClientReceiptValidator $validator,
    ) {}

    public function global(Request $request): Response
    {
        return Response::html(
            $this->view->render("modules/client-receipts/index", [
                "title" => "سندات قبض العملاء",
                "contract" => null,
                "summary" => null,
                "references" => $this->service->references(),
                "canCreate" => false,
            ]),
        );
    }

    public function contract(Request $request): Response
    {
        $id = (int) $request->route("contract_id");
        $contract = $this->service->contract($id);
        return Response::html(
            $this->view->render("modules/client-receipts/index", [
                "title" => "سندات قبض العميل",
                "contract" => $contract,
                "summary" => $this->service->financialSummary($id),
                "references" => $this->service->references(),
                "canCreate" =>
                    $this->auth->can("client_receipts.create") &&
                    in_array(
                        $contract["status"],
                        ["active", "suspended", "completed"],
                        true,
                    ),
            ]),
        );
    }

    public function create(Request $request): Response
    {
        return $this->form((int) $request->route("contract_id"), null);
    }
    public function edit(Request $request): Response
    {
        $receipt = $this->service->find((int) $request->route("id"));
        return $this->form(
            (int) $receipt["client_contract_id"],
            (int) $receipt["id"],
        );
    }

    public function show(Request $request): Response
    {
        $receipt = $this->service->find((int) $request->route("id"));
        return Response::html(
            $this->view->render("modules/client-receipts/show", [
                "title" => "تفاصيل سند القبض",
                "receipt" => $receipt,
                "allocations" => $this->allocations->allocationHistory(
                    (int) $receipt["id"],
                ),
                "canEdit" => $this->auth->can("client_receipts.edit"),
                "canPost" => $this->auth->can("client_receipts.post"),
                "canCancel" => $this->auth->can("client_receipts.cancel"),
                "canAllocate" => $this->auth->can("client_receipts.allocate"),
            ]),
        );
    }

    public function print(Request $request): Response
    {
        $receipt = $this->service->find((int) $request->route("id"));
        if ($receipt["status"] !== "posted") {
            throw new BusinessRuleException(
                "يمكن طباعة سندات القبض المرحلة فقط.",
            );
        }
        return Response::html(
            $this->view->render("modules/client-receipts/print", [
                "title" => "طباعة سند قبض",
                "receipt" => $receipt,
                "allocations" => $this->allocations->allocationHistory(
                    (int) $receipt["id"],
                ),
            ]),
        );
    }

    public function store(Request $request): Response
    {
        return $this->save(
            $request,
            null,
            (int) $request->route("contract_id"),
        );
    }
    public function update(Request $request): Response
    {
        $receipt = $this->service->find((int) $request->route("id"));
        return $this->save(
            $request,
            (int) $receipt["id"],
            (int) $receipt["client_contract_id"],
        );
    }
    public function post(Request $request): Response
    {
        return $this->action($request, "post");
    }
    public function cancel(Request $request): Response
    {
        return $this->action($request, "cancel");
    }

    private function form(int $contractId, ?int $id): Response
    {
        $contract = $this->service->contract($contractId);
        $receipt = $id === null ? null : $this->service->find($id);
        if ($receipt !== null && $receipt["status"] !== "draft") {
            throw new BusinessRuleException("سند القبض غير قابل للتعديل.");
        }
        return Response::html(
            $this->view->render("modules/client-receipts/form", [
                "title" =>
                    $id === null ? "إضافة سند قبض" : "تعديل مسودة سند القبض",
                "contract" => $contract,
                "receipt" => $receipt,
            ]),
        );
    }

    private function save(Request $request, ?int $id, int $contractId): Response
    {
        if (!$this->csrf->isValid($request->input("_token"))) {
            return Response::html("انتهت صلاحية الطلب.", 419);
        }
        try {
            $id = $this->service->save(
                $this->validator->validate($request->all(), $contractId),
                $id,
            );
            $this->flash("success", "تم الحفظ", "تم حفظ مسودة سند القبض.");
            return Response::redirect("/client-receipts/" . $id);
        } catch (ValidationException | BusinessRuleException $exception) {
            $message =
                $exception instanceof ValidationException
                    ? (string) reset(
                        $exception->errors[array_key_first($exception->errors)],
                    )
                    : $exception->getMessage();
            $this->flash("error", "تعذر الحفظ", $message);
            return Response::redirect(
                $id === null
                    ? "/client-contracts/" . $contractId . "/receipts/create"
                    : "/client-receipts/" . $id . "/edit",
            );
        }
    }

    private function action(Request $request, string $action): Response
    {
        if (!$this->csrf->isValid($request->input("_token"))) {
            return Response::html("انتهت صلاحية الطلب.", 419);
        }
        $id = (int) $request->route("id");
        try {
            $action === "post"
                ? $this->service->post($id)
                : $this->service->cancel($id);
            $this->flash(
                "success",
                "تم التحديث",
                $action === "post"
                    ? "تم ترحيل سند القبض."
                    : "تم إلغاء مسودة سند القبض.",
            );
        } catch (BusinessRuleException $exception) {
            $this->flash("error", "تعذر التحديث", $exception->getMessage());
        }
        return Response::redirect("/client-receipts/" . $id);
    }

    private function flash(string $type, string $title, string $text): void
    {
        $this->session->flash("alert", compact("type", "title", "text"));
    }
}
