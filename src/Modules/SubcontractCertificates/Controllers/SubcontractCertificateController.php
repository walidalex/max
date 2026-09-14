<?php

declare(strict_types=1);

namespace App\Modules\SubcontractCertificates\Controllers;

use App\Core\Exceptions\BusinessRuleException;
use App\Core\Exceptions\ValidationException;
use App\Core\Http\Csrf;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\Session;
use App\Core\View\View;
use App\Modules\AccessControl\Services\AuthorizationService;
use App\Modules\SubcontractCertificates\Services\SubcontractCertificateService;
use App\Modules\SubcontractCertificates\Validators\SubcontractCertificateValidator;

final class SubcontractCertificateController
{
    public function __construct(
        private readonly View $view,
        private readonly Csrf $csrf,
        private readonly Session $session,
        private readonly AuthorizationService $authorization,
        private readonly SubcontractCertificateService $service,
        private readonly SubcontractCertificateValidator $validator,
    ) {}

    public function index(Request $request): Response
    {
        $subcontract = $this->service->subcontract((int) $request->route('subcontract_id'));
        return Response::html($this->view->render('modules/subcontract-certificates/index', [
            'title' => 'مستخلصات أعمال مقاول الباطن',
            'subcontract' => $subcontract,
            'approvedEarnedValue' => $this->service->getApprovedEarnedValue((int) $subcontract['id']),
            'canCreate' => $this->authorization->can('subcontract_certificates.create') && in_array($subcontract['status'], ['active', 'suspended'], true),
        ]));
    }

    public function create(Request $request): Response { return $this->form((int) $request->route('subcontract_id'), null); }

    public function edit(Request $request): Response
    {
        $certificate = $this->service->find((int) $request->route('id'));
        return $this->form((int) $certificate['subcontract_id'], (int) $certificate['id']);
    }

    public function show(Request $request): Response
    {
        $certificate = $this->service->find((int) $request->route('id'));
        $items = $certificate['pricing_method'] === 'boq' ? $this->service->certificateItems((int) $certificate['id']) : [];
        return Response::html($this->view->render('modules/subcontract-certificates/show', [
            'title' => 'تفاصيل مستخلص الأعمال',
            'certificate' => $certificate,
            'items' => $items,
            'canEdit' => $this->authorization->can('subcontract_certificates.edit'),
            'canApprove' => $this->authorization->can('subcontract_certificates.approve'),
            'canCancel' => $this->authorization->can('subcontract_certificates.cancel'),
        ]));
    }

    public function store(Request $request): Response { return $this->save($request, null, (int) $request->route('subcontract_id')); }

    public function update(Request $request): Response
    {
        $certificate = $this->service->find((int) $request->route('id'));
        return $this->save($request, (int) $certificate['id'], (int) $certificate['subcontract_id']);
    }

    public function approve(Request $request): Response { return $this->action($request, 'approve'); }
    public function cancel(Request $request): Response { return $this->action($request, 'cancel'); }

    private function form(int $subcontractId, ?int $certificateId): Response
    {
        return Response::html($this->view->render('modules/subcontract-certificates/form', [
            'title' => $certificateId === null ? 'إضافة مستخلص أعمال' : 'تعديل مسودة المستخلص',
            ...$this->service->formData($subcontractId, $certificateId),
        ]));
    }

    private function save(Request $request, ?int $id, int $subcontractId): Response
    {
        if (!$this->csrf->isValid($request->input('_token'))) return Response::html('انتهت صلاحية الطلب.', 419);
        try {
            $subcontract = $this->service->subcontract($subcontractId);
            $id = $this->service->save($this->validator->validate($request->all(), $subcontractId, (string) $subcontract['pricing_method']), $id);
            $this->flash('success', 'تم الحفظ', 'تم حفظ مسودة المستخلص.');
            return Response::redirect('/subcontract-certificates/' . $id);
        } catch (ValidationException|BusinessRuleException $exception) {
            $message = $exception instanceof ValidationException ? (string) reset($exception->errors[array_key_first($exception->errors)]) : $exception->getMessage();
            $this->flash('error', 'تعذر الحفظ', $message);
            return Response::redirect($id === null ? "/subcontracts/{$subcontractId}/certificates/create" : "/subcontract-certificates/{$id}/edit");
        }
    }

    private function action(Request $request, string $action): Response
    {
        if (!$this->csrf->isValid($request->input('_token'))) return Response::html('انتهت صلاحية الطلب.', 419);
        $id = (int) $request->route('id');
        try {
            $action === 'approve' ? $this->service->approve($id) : $this->service->cancel($id);
            $this->flash('success', 'تم التحديث', $action === 'approve' ? 'تم اعتماد المستخلص وتجميد قيمه.' : 'تم إلغاء مسودة المستخلص.');
        } catch (BusinessRuleException $exception) {
            $this->flash('error', 'تعذر التحديث', $exception->getMessage());
        }
        return Response::redirect('/subcontract-certificates/' . $id);
    }

    private function flash(string $type, string $title, string $text): void { $this->session->flash('alert', compact('type', 'title', 'text')); }
}
