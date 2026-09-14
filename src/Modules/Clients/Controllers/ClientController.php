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
use App\Modules\AccessControl\Services\AuthorizationService;
use App\Modules\Clients\Services\ClientService;
use App\Modules\Clients\Services\ContactService;
use App\Modules\Clients\Validators\ClientValidator;

final class ClientController
{
    public function __construct(
        private readonly View $view,
        private readonly Csrf $csrf,
        private readonly Session $session,
        private readonly AuthorizationService $authorization,
        private readonly ClientService $clients,
        private readonly ContactService $contacts,
        private readonly ClientValidator $validator,
    ) {}

    public function index(Request $request): Response
    {
        return Response::html($this->view->render('modules/clients/index', [
            'title' => 'العملاء',
            'canCreate' => $this->authorization->can('clients.create'),
            'canEdit' => $this->authorization->can('clients.edit'),
            'canActivate' => $this->authorization->can('clients.activate'),
        ]));
    }

    public function create(Request $request): Response
    {
        return $this->form(null);
    }

    public function edit(Request $request): Response
    {
        return $this->form((int) $request->route('id'));
    }

    public function show(Request $request): Response
    {
        $id = (int) $request->route('id');
        $client = $this->clients->find($id);
        return Response::html($this->view->render('modules/clients/show', [
            'title' => 'تفاصيل العميل',
            'client' => $client,
            'contacts' => $this->contacts->forClient($id),
            'canEdit' => $this->authorization->can('clients.edit'),
            'canActivate' => $this->authorization->can('clients.activate'),
        ]));
    }

    public function store(Request $request): Response
    {
        return $this->save($request, null);
    }

    public function update(Request $request): Response
    {
        return $this->save($request, (int) $request->route('id'));
    }

    public function activate(Request $request): Response
    {
        if (!$this->csrf->isValid($request->input('_token'))) {
            return Response::html('انتهت صلاحية الطلب.', 419);
        }
        $id = (int) $request->route('id');
        try {
            $this->clients->setActive($id, (bool) (int) $request->input('active', 0));
            $this->success('تم تحديث حالة العميل.');
        } catch (BusinessRuleException $exception) {
            $this->error($exception->getMessage());
        }
        return Response::redirect('/clients');
    }

    private function form(?int $id): Response
    {
        $client = $id === null ? null : $this->clients->find($id);
        return Response::html($this->view->render('modules/clients/form', [
            'title' => $id === null ? 'إضافة عميل' : 'تعديل العميل',
            'client' => $client,
        ]));
    }

    private function save(Request $request, ?int $id): Response
    {
        if (!$this->csrf->isValid($request->input('_token'))) {
            return Response::html('انتهت صلاحية الطلب.', 419);
        }
        try {
            $id = $this->clients->save($this->validator->validate($request->all()), $id);
            $this->success('تم حفظ بيانات العميل.');
            return Response::redirect("/clients/{$id}");
        } catch (ValidationException|BusinessRuleException $exception) {
            $this->error($this->message($exception));
            return Response::redirect($id === null ? '/clients/create' : "/clients/{$id}/edit");
        }
    }

    private function success(string $text): void
    {
        $this->session->flash('alert', ['type' => 'success', 'title' => 'تم الحفظ', 'text' => $text]);
    }

    private function error(string $text): void
    {
        $this->session->flash('alert', ['type' => 'error', 'title' => 'تعذر تنفيذ الطلب', 'text' => $text]);
    }

    private function message(\Throwable $exception): string
    {
        return $exception instanceof ValidationException
            ? (string) reset($exception->errors[array_key_first($exception->errors)])
            : $exception->getMessage();
    }
}
