<?php

declare(strict_types=1);

namespace App\Modules\Foundation\Controllers;

use App\Core\Exceptions\ValidationException;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\Session;
use App\Core\Http\Csrf;
use App\Core\View\View;
use App\Modules\Foundation\Services\FoundationService;
use App\Modules\Foundation\Validators\FoundationCheckValidator;

final class FoundationController
{
    public function __construct(
        private readonly View $view,
        private readonly Session $session,
        private readonly Csrf $csrf,
        private readonly FoundationCheckValidator $validator,
        private readonly FoundationService $service,
    ) {}

    public function index(Request $request): Response
    {
        return Response::html($this->view->render('modules/foundation/index', ['title' => 'لوحة البداية']));
    }

    public function check(Request $request): Response
    {
        if (!$this->csrf->isValid($request->input('_token'))) {
            return Response::html('انتهت صلاحية الطلب. حدّث الصفحة وحاول مجددًا.', 419);
        }
        try {
            $data = $this->validator->validate($request->all());
            $result = $this->service->check($data);
            $this->session->flash('alert', ['type' => 'success', 'title' => 'تم الاتصال بنجاح', 'text' => "{$result['label']} — {$result['database_version']}"]);
        } catch (ValidationException $exception) {
            $this->session->flash('alert', ['type' => 'error', 'title' => 'بيانات غير صالحة', 'text' => (string) reset($exception->errors['label'])]);
        }
        return Response::redirect('/');
    }

    public function health(Request $request): Response
    {
        return Response::json(['status' => 'ok', 'application' => 'ready', 'timestamp' => date(DATE_ATOM)]);
    }
}
