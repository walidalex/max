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
use App\Modules\ClientReceipts\Services\ClientReceiptAllocationService;
use App\Modules\ClientReceipts\Services\ClientReceiptService;
use App\Modules\ClientReceipts\Validators\ClientReceiptAllocationValidator;

final class ClientReceiptAllocationController
{
    public function __construct(
        private readonly View $view,
        private readonly Csrf $csrf,
        private readonly Session $session,
        private readonly ClientReceiptService $receipts,
        private readonly ClientReceiptAllocationService $service,
        private readonly ClientReceiptAllocationValidator $validator,
    ) {}
    public function form(Request $request): Response
    {
        $id = (int) $request->route("id");
        $receipt = $this->receipts->find($id);
        if ($receipt["status"] !== "posted") {
            throw new BusinessRuleException("يمكن تخصيص سند قبض مرحّل فقط.");
        }
        return Response::html(
            $this->view->render("modules/client-receipts/allocate", [
                "title" => "تخصيص سند القبض",
                "receipt" => $receipt,
                "statements" => $this->service->eligibleStatements($id),
            ]),
        );
    }
    public function store(Request $request): Response
    {
        if (!$this->csrf->isValid($request->input("_token"))) {
            return Response::html("انتهت صلاحية الطلب.", 419);
        }
        $id = (int) $request->route("id");
        try {
            $this->service->allocate(
                $id,
                $this->validator->validate($request->all()),
            );
            $this->flash("success", "تم التخصيص", "تم حفظ تخصيصات سند القبض.");
        } catch (ValidationException | BusinessRuleException $exception) {
            $message =
                $exception instanceof ValidationException
                    ? (string) reset(
                        $exception->errors[array_key_first($exception->errors)],
                    )
                    : $exception->getMessage();
            $this->flash("error", "تعذر التخصيص", $message);
            return Response::redirect("/client-receipts/" . $id . "/allocate");
        }
        return Response::redirect("/client-receipts/" . $id);
    }
    public function eligible(Request $request): Response
    {
        return Response::json([
            "data" => $this->service->eligibleStatements(
                (int) $request->route("id"),
            ),
        ]);
    }
    private function flash(string $type, string $title, string $text): void
    {
        $this->session->flash("alert", compact("type", "title", "text"));
    }
}
