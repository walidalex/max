<?php

declare(strict_types=1);

namespace App\Modules\Clients\Controllers;

use App\Core\Exceptions\BusinessRuleException;
use App\Core\Exceptions\ValidationException;
use App\Core\Http\Csrf;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\Session;
use App\Core\View\View;
use App\Modules\Clients\Services\ClientService;
use App\Modules\Clients\Services\ContactService;
use App\Modules\Clients\Validators\ContactValidator;

final class ClientContactController
{
    public function __construct(
        private readonly View $view,
        private readonly Csrf $csrf,
        private readonly Session $session,
        private readonly ClientService $clients,
        private readonly ContactService $contacts,
        private readonly ContactValidator $validator,
    ) {}

    public function create(Request $request): Response
    {
        return $this->form((int) $request->route('client_id'), null);
    }

    public function edit(Request $request): Response
    {
        return $this->form((int) $request->route('client_id'), (int) $request->route('contact_id'));
    }

    public function store(Request $request): Response
    {
        return $this->save($request, null);
    }

    public function update(Request $request): Response
    {
        return $this->save($request, (int) $request->route('contact_id'));
    }

    public function primary(Request $request): Response
    {
        return $this->action($request, fn (int $contactId, int $clientId) => $this->contacts->markPrimary($contactId, $clientId), 'تم تعيين جهة الاتصال الأساسية.');
    }

    public function remove(Request $request): Response
    {
        return $this->action($request, fn (int $contactId, int $clientId) => $this->contacts->remove($contactId, $clientId), 'تم حذف جهة الاتصال.');
    }

    private function form(int $clientId, ?int $contactId): Response
    {
        return Response::html($this->view->render('modules/clients/contact-form', [
            'title' => $contactId === null ? 'إضافة جهة اتصال' : 'تعديل جهة الاتصال',
            'client' => $this->clients->find($clientId),
            'contact' => $contactId === null ? null : $this->contacts->find($contactId, $clientId),
        ]));
    }

    private function save(Request $request, ?int $contactId): Response
    {
        if (!$this->csrf->isValid($request->input('_token'))) {
            return Response::html('انتهت صلاحية الطلب.', 419);
        }
        $clientId = (int) $request->route('client_id');
        try {
            $this->contacts->save($clientId, $this->validator->validate($request->all()), $contactId);
            $this->flash('success', 'تم الحفظ', 'تم حفظ جهة الاتصال.');
            return Response::redirect("/clients/{$clientId}");
        } catch (ValidationException|BusinessRuleException $exception) {
            $message = $exception instanceof ValidationException
                ? (string) reset($exception->errors[array_key_first($exception->errors)])
                : $exception->getMessage();
            $this->flash('error', 'تعذر الحفظ', $message);
            $path = $contactId === null ? "/clients/{$clientId}/contacts/create" : "/clients/{$clientId}/contacts/{$contactId}/edit";
            return Response::redirect($path);
        }
    }

    private function action(Request $request, callable $action, string $success): Response
    {
        if (!$this->csrf->isValid($request->input('_token'))) {
            return Response::html('انتهت صلاحية الطلب.', 419);
        }
        $clientId = (int) $request->route('client_id');
        try {
            $action((int) $request->route('contact_id'), $clientId);
            $this->flash('success', 'تم التحديث', $success);
        } catch (BusinessRuleException $exception) {
            $this->flash('error', 'تعذر تنفيذ الطلب', $exception->getMessage());
        }
        return Response::redirect("/clients/{$clientId}");
    }

    private function flash(string $type, string $title, string $text): void
    {
        $this->session->flash('alert', compact('type', 'title', 'text'));
    }
}
