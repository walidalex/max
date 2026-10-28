<?php

declare(strict_types=1);

namespace App\Modules\ClientContracts\Controllers;

use App\Core\Exceptions\BusinessRuleException;
use App\Core\Exceptions\ValidationException;
use App\Core\Http\Csrf;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\Session;
use App\Core\View\View;
use App\Modules\AccessControl\Services\AuthorizationService;
use App\Modules\ClientContracts\Services\ProjectContractWizardService;
use App\Modules\ClientContracts\Validators\ProjectContractWizardValidator;
use App\Modules\Projects\Services\ProjectService;

final class ProjectContractWizardController
{
    public function __construct(
        private readonly View $view,
        private readonly Csrf $csrf,
        private readonly Session $session,
        private readonly AuthorizationService $auth,
        private readonly ProjectService $projects,
        private readonly ProjectContractWizardValidator $validator,
        private readonly ProjectContractWizardService $service,
    ) {}

    public function create(Request $request): Response
    {
        return $this->form();
    }

    public function contacts(Request $request): Response
    {
        if (!$this->canCreate()) {
            return Response::json(['message' => 'ليس لديك صلاحية عرض جهات الاتصال.'], 403);
        }
        $id = filter_var($request->input('id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) {
            return Response::json(['message' => 'الاختيار غير صالح.'], 422);
        }
        try {
            $data = $this->projects->contacts($id);
            return Response::json(['data' => $data]);
        } catch (BusinessRuleException $exception) {
            return Response::json(['message' => $exception->getMessage()], 422);
        }
    }

    public function store(Request $request): Response
    {
        if (!$this->csrf->isValid($request->input('_token'))) {
            return Response::html('انتهت صلاحية الطلب. أعد تحميل الصفحة.', 419);
        }
        if (!$this->canCreate()) {
            return Response::html('ليس لديك صلاحية الإضافة.', 403);
        }
        try {
            $this->service->save($this->validator->validate($request->all()));
            $this->session->flash('alert', ['type' => 'success', 'title' => 'تم الحفظ', 'text' => 'تم حفظ المشروع وعقد العميل.']);
            return Response::redirect('/project-contracts');
        } catch (ValidationException | BusinessRuleException $exception) {
            $errors = $exception instanceof ValidationException ? $exception->errors : ['wizard' => [$exception->getMessage()]];
            return $this->form($request->all(), $errors, 422);
        }
    }

    private function canCreate(): bool
    {
        return $this->auth->can('projects.create') && $this->auth->can('client_contracts.create');
    }

    private function form(array $old = [], array $errors = [], int $status = 200): Response
    {
        if (!$this->canCreate()) {
            return Response::html('ليس لديك صلاحية الإضافة.', 403);
        }
        $refs = $this->projects->referenceData();
        return Response::html($this->view->render('modules/project-contracts/wizard', [
            'title' => 'إضافة مشروع وعقد عميل',
            ...$refs,
            'canCreateClient' => $this->auth->can('clients.create'),
            'old' => $old,
            'errors' => $errors,
        ]), $status);
    }
}
