<?php declare(strict_types=1);
namespace App\Modules\ClientProgressStatements\Controllers;
use App\Modules\AccessControl\Services\AuthorizationService;
use App\Core\Exceptions\BusinessRuleException;
use App\Core\Exceptions\ValidationException;
use App\Core\Http\Csrf;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\Session;
use App\Core\View\View;
use App\Modules\ClientProgressStatements\Services\ClientProgressStatementService;
use App\Modules\ClientProgressStatements\Validators\ClientProgressStatementValidator;
use App\Modules\ClientProgressStatements\Validators\StatementContractSelectionValidator;
final class ClientProgressStatementController
{
    public function __construct(
        private readonly View $view,
        private readonly ClientProgressStatementService $service,
        private readonly ClientProgressStatementValidator $validator,
        private readonly Csrf $csrf,
        private readonly Session $session,
        private readonly AuthorizationService $authorization,
        private readonly StatementContractSelectionValidator $selectionValidator,
    ) {}
    public function global(Request $r): Response
    {
        return $this->listing(null);
    }
    public function contract(Request $r): Response
    {
        return $this->listing((int) $r->route("contract_id"));
    }
    public function selectContract(Request $request): Response
    {
        $errors = [];
        if ($request->input('contract_id') !== null || $request->input('project_id') !== null) {
            try {
                $id = $this->service->selectCreationContract($this->selectionValidator->validate($request->all()));
                return Response::redirect('/client-contracts/'.$id.'/progress-statements/create');
            } catch (ValidationException | BusinessRuleException $exception) {
                $errors = $exception instanceof ValidationException ? $exception->errors : ['contract_id'=>[$exception->getMessage()]];
            }
        }
        return Response::html($this->view->render('modules/client-progress-statements/select-contract', [
            'title'=>'إنشاء مستخلص عميل',
            'contracts'=>$this->service->creationContracts(),
            'old'=>$request->all(),
            'errors'=>$errors,
        ]), $errors === [] ? 200 : 422);
    }
    private function listing(?int $id): Response
    {
        $contract = $id ? $this->service->contract($id) : null;
        $canCreate = $this->authorization->can('client_progress_statements.create');
        if ($contract && $canCreate) {
            try { $this->service->creationContract((int)$contract['id']); }
            catch (BusinessRuleException) { $canCreate = false; }
        }
        return Response::html(
            $this->view->render("modules/client-progress-statements/index", [
                "title" => "مستخلصات العملاء",
                "contract" => $contract,
                "contractId" => $id,
                "references" => $this->service->references(),
                "scopeRows" => $contract?$this->service->scopeRows((int)$contract['id'],date('Y-m-d')):[],
                "canCreate" => $canCreate,
            ]),
        );
    }
    public function create(Request $r): Response
    {
        $contract = $this->service->creationContract((int) $r->route("contract_id"));
        $date = date("Y-m-d");
        return $this->form(
            $contract,
            null,
            $this->service->getEligibleActualCostsForContract(
                (int) $contract["id"],
                $date,
            ),
            $this->service->getAvailableVariationsForContract(
                (int) $contract["id"],
            ),
            $contract['pricing_method']==='boq'?$this->service->getBoqItemsForContract((int)$contract['id']):[],
        );
    }
    public function preview(Request $request): Response
    {
        if (!$this->authorization->can('client_progress_statements.create') && !$this->authorization->can('client_progress_statements.edit')) {
            return Response::json(['message'=>'ليس لديك صلاحية إنشاء أو تعديل المستخلص.'],403);
        }
        try {
            $data=$this->validator->validate(['statement_date'=>$request->input('statement_date')]);
            $contract=$this->service->creationContract((int)$request->route('contract_id'));
            $statementId=null;
            if ($request->input('statement_id') !== null && $request->input('statement_id') !== '') {
                $value=filter_var($request->input('statement_id'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
                if ($value === false) throw new BusinessRuleException('المستخلص المحدد غير صالح.');
                $statement=$this->service->find($value);
                if ((int)$statement['client_contract_id'] !== (int)$contract['id'] || $statement['status'] !== 'draft') throw new BusinessRuleException('يمكن تحديث معاينة مسودة تابعة للعقد فقط.');
                $statementId=$value;
            }
            $rows=$this->service->scopeRows((int)$contract['id'],$data->statementDate,$statementId);
            $costs=$this->service->getEligibleActualCostsForContract((int)$contract['id'],$data->statementDate);
            return Response::json([
                'scopeHtml'=>$this->view->component('cost-plus-scope-table',['scopeRows'=>$rows]),
                'costsHtml'=>$this->view->component('client-statement-costs',['costs'=>$costs]),
            ]);
        } catch (ValidationException | BusinessRuleException $exception) {
            return Response::json(['message'=>$exception instanceof BusinessRuleException?$exception->getMessage():'تاريخ المستخلص غير صالح.'],422);
        }
    }
    public function store(Request $r): Response
    {
        return $this->save($r, (int) $r->route("contract_id"), null);
    }
    public function show(Request $r): Response
    {
        $d = $this->service->details((int) $r->route("id"));
        return Response::html(
            $this->view->render("modules/client-progress-statements/show", [
                "title" => "تفاصيل مستخلص العميل",
                ...$d,
                "canEdit" => $this->authorization->can(
                    "client_progress_statements.edit",
                ),
                "canApprove" => $this->authorization->can(
                    "client_progress_statements.approve",
                ),
                "canCancel" => $this->authorization->can(
                    "client_progress_statements.cancel",
                ),
            ]),
        );
    }
    public function edit(Request $r): Response
    {
        $id = (int) $r->route("id");
        $s = $this->service->find($id);
        if ($s["status"] !== "draft") {
            return Response::redirect("/client-progress-statements/" . $id);
        }
        return $this->form(
            $this->service->contract((int) $s["client_contract_id"]),
            $s,
            $this->service->getEligibleActualCosts($id),
            $this->service->getAvailableVariations($id),
            $s['pricing_method']==='boq'?$this->service->getBoqItems($id):[],
        );
    }
    public function update(Request $r): Response
    {
        $s = $this->service->find((int) $r->route("id"));
        return $this->save($r, (int) $s["client_contract_id"], (int) $s["id"]);
    }
    public function approve(Request $r): Response
    {
        return $this->action($r, "approve", "تم اعتماد مستخلص العميل.");
    }
    public function cancel(Request $r): Response
    {
        return $this->action($r, "cancel", "تم إلغاء مسودة المستخلص.");
    }
    public function print(Request $r): Response
    {
        $d = $this->service->details((int) $r->route("id"));
        if ($d["statement"]["status"] !== "approved") {
            throw new BusinessRuleException(
                "الطباعة متاحة للمستخلص المعتمد فقط.",
            );
        }
        return Response::html(
            $this->view->render(
                "modules/client-progress-statements/print",
                ["title" => "طباعة مستخلص العميل", ...$d],
                "layouts/print",
            ),
        );
    }
    private function form(
        array $c,
        ?array $s,
        array $costs,
        array $vars,
        array $boqItems,
    ): Response {
        return Response::html(
            $this->view->render("modules/client-progress-statements/form", [
                "title" => $s ? "تعديل مستخلص العميل" : "إنشاء مستخلص عميل",
                "contract" => $c,
                "statement" => $s,
                "costs" => $costs,
                "variations" => $vars,
                "boqItems" => $boqItems,
                "scopeRows" => $this->service->scopeRows((int)$c['id'],(string)($s['statement_date']??date('Y-m-d')),$s===null?null:(int)$s['id']),
            ]),
        );
    }
    private function save(Request $r, int $c, ?int $id): Response
    {
        if (!$this->csrf->isValid($r->input("_token"))) {
            return Response::html("انتهت صلاحية الطلب.", 419);
        }
        try {
            $saved = $this->service->save(
                $c,
                $this->validator->validate($r->all()),
                $id,
            );
            $this->flash(
                "success",
                "تم الحفظ",
                $id
                    ? "تم تحديث مسودة المستخلص."
                    : "تم إنشاء مسودة المستخلص.",
            );
            return Response::redirect(
                "/client-progress-statements/" . $saved . "/edit",
            );
        } catch (ValidationException | BusinessRuleException $e) {
            $m =
                $e instanceof ValidationException
                    ? (string) reset($e->errors[array_key_first($e->errors)])
                    : $e->getMessage();
            $this->flash("error", "تعذر الحفظ", $m);
            return Response::redirect(
                $id
                    ? "/client-progress-statements/" . $id . "/edit"
                    : "/client-contracts/" . $c . "/progress-statements/create",
            );
        }
    }
    private function action(Request $r, string $a, string $m): Response
    {
        if (!$this->csrf->isValid($r->input("_token"))) {
            return Response::html("انتهت صلاحية الطلب.", 419);
        }
        $id = (int) $r->route("id");
        try {
            $this->service->{$a}($id);
            $this->flash("success", "تم التحديث", $m);
        } catch (BusinessRuleException $e) {
            $this->flash("error", "تعذر التحديث", $e->getMessage());
        }
        return Response::redirect("/client-progress-statements/" . $id);
    }
    private function flash(string $t, string $title, string $text): void
    {
        $this->session->flash("alert", [
            "type" => $t,
            "title" => $title,
            "text" => $text,
        ]);
    }
}
