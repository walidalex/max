<?php

declare(strict_types=1);

namespace App\Modules\Subcontracts\Controllers;

use App\Core\Exceptions\BusinessRuleException;
use App\Core\Exceptions\ValidationException;
use App\Core\Http\Csrf;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\Session;
use App\Core\View\View;
use App\Modules\Subcontracts\Services\SubcontractService;
use App\Modules\Subcontracts\Validators\SubcontractValidator;

final class SubcontractController
{
    public function __construct(private readonly View $view, private readonly Csrf $csrf, private readonly Session $session, private readonly SubcontractService $service, private readonly SubcontractValidator $validator) {}

    public function index(Request $request): Response { return Response::html($this->view->render('modules/subcontracts/index', ['title' => 'عقود مقاولي الباطن', 'rows' => $this->service->all()])); }
    public function create(Request $request): Response { return $this->form(null); }
    public function edit(Request $request): Response { return $this->form((int) $request->route('id')); }
    public function show(Request $request): Response { $subcontract = $this->service->find((int) $request->route('id')); return Response::html($this->view->render('modules/subcontracts/show', ['title' => 'تفاصيل عقد مقاول الباطن', 'subcontract' => $subcontract, 'transitions' => $this->service->transitions($subcontract['status'])])); }
    public function store(Request $request): Response { return $this->save($request, null); }
    public function update(Request $request): Response { return $this->save($request, (int) $request->route('id')); }

    public function status(Request $request): Response
    {
        if (!$this->csrf->isValid($request->input('_token'))) return Response::html('انتهت صلاحية الطلب.', 419);
        $id = (int) $request->route('id');
        try { $this->service->change($id, (string) $request->input('status')); $this->flash('success', 'تم التحديث', 'تم تغيير الحالة.'); }
        catch (BusinessRuleException $exception) { $this->flash('error', 'تعذر التحديث', $exception->getMessage()); }
        return Response::redirect('/subcontracts/' . $id);
    }

    private function form(?int $id): Response
    {
        return Response::html($this->view->render('modules/subcontracts/form', ['title' => $id ? 'تعديل عقد مقاول باطن' : 'إضافة عقد مقاول باطن', 'subcontract' => $id ? $this->service->find($id) : null, ...$this->service->refs()]));
    }

    private function save(Request $request, ?int $id): Response
    {
        if (!$this->csrf->isValid($request->input('_token'))) return Response::html('انتهت صلاحية الطلب.', 419);
        try { $id = $this->service->save($this->validator->validate($request->all()), $id); return Response::redirect('/subcontracts/' . $id); }
        catch (ValidationException|BusinessRuleException $exception) {
            $message = $exception instanceof ValidationException ? (string) reset($exception->errors[array_key_first($exception->errors)]) : $exception->getMessage();
            $this->flash('error', 'تعذر الحفظ', $message);
            return Response::redirect($id ? '/subcontracts/' . $id . '/edit' : '/subcontracts/create');
        }
    }

    private function flash(string $type, string $title, string $text): void { $this->session->flash('alert', compact('type', 'title', 'text')); }
}
