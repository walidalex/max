<?php

declare(strict_types=1);

use App\Core\Auth\Auth;
use App\Core\Database\Database;
use App\Core\Exceptions\BusinessRuleException;
use App\Core\Exceptions\ValidationException;
use App\Core\Http\Request;
use App\Modules\ClientProgressStatements\Controllers\ClientProgressStatementController;
use App\Modules\ClientProgressStatements\DTOs\StatementContractSelectionData;
use App\Modules\ClientProgressStatements\Services\ClientProgressStatementService;
use App\Modules\ClientProgressStatements\Validators\StatementContractSelectionValidator;

(static function () use ($app): void {
    $db = $app->make(Database::class);
    $auth = $app->make(Auth::class);
    $previousUser = $auth->user();
    $service = $app->make(ClientProgressStatementService::class);
    $controller = $app->make(ClientProgressStatementController::class);
    $suffix = bin2hex(random_bytes(5));
    $clientId = $userId = $roleId = null;
    $contracts = $projects = [];
    $assert = static function (bool $condition, string $message): void {
        if (!$condition) throw new RuntimeException($message);
    };
    try {
        $db->execute('INSERT INTO users(username,name,password_hash) VALUES(?,?,?)', ['cps-entry-'.$suffix, 'CPS Entry Test', password_hash($suffix, PASSWORD_DEFAULT)]);
        $userId = (int)$db->connection()->insert_id;
        $role = $db->execute("SELECT id FROM roles WHERE code='super_admin'")->fetch_assoc();
        $db->execute('INSERT INTO user_roles(user_id,role_id) VALUES(?,?)', [$userId,(int)$role['id']]);
        $auth->refresh(['id'=>$userId,'name'=>'CPS Entry Test']);
        $db->execute("INSERT INTO clients(client_code,client_type,name) VALUES(?,'individual',?)", ['CPE-'.$suffix,'CPS Entry Client']);
        $clientId = (int)$db->connection()->insert_id;
        $make = function (string $key, string $method, string $status, bool $approvedBoq = false) use ($db, $suffix, $clientId, $userId, &$contracts, &$projects): array {
            $db->execute("INSERT INTO projects(project_code,name,client_id,project_type,status) VALUES(?,?,?,'contracting','active')", ['CPE-'.$suffix.'-'.$key,'Entry '.$key,$clientId]);
            $project = (int)$db->connection()->insert_id;
            $projects[] = $project;
            $db->execute('INSERT INTO client_contracts(contract_code,contract_number,project_id,client_id,pricing_method,contract_date,title,markup_percentage,contract_value,status) VALUES(?,?,?,?,?,?,?,?,?,?)', ['CPE-'.$suffix.'-'.$key,'CPEN-'.$suffix.'-'.$key,$project,$clientId,$method,'2026-10-29','Entry '.$key,$method==='cost_plus'?'15.0000':null,$method==='lump_sum'?'1000.00':null,$status]);
            $contract = (int)$db->connection()->insert_id;
            $contracts[] = $contract;
            if ($approvedBoq) $db->execute("INSERT INTO contract_boqs(client_contract_id,status,approved_at,approved_by) VALUES(?,'approved',NOW(),?)", [$contract,$userId]);
            return ['project'=>$project,'contract'=>$contract];
        };
        $active = $make('active','cost_plus','active');
        $suspended = $make('suspended','cost_plus','suspended');
        $completed = $make('completed','cost_plus','completed');
        $boq = $make('approved-boq','boq','active',true);
        $draft = $make('draft','cost_plus','draft');
        $cancelled = $make('cancelled','cost_plus','cancelled');
        $lump = $make('lump','lump_sum','active');
        $unapproved = $make('unapproved-boq','boq','active');
        $ids = array_map('intval', array_column($service->creationContracts(), 'id'));
        foreach ([$active,$suspended,$completed,$boq] as $fixture) {
            $assert(in_array($fixture['contract'],$ids,true),'Eligible contract missing from creation choices.');
            $assert($service->selectCreationContract(new StatementContractSelectionData($fixture['project'],$fixture['contract']))===$fixture['contract'],'Eligible selection failed.');
        }
        foreach ([$draft,$cancelled,$lump,$unapproved] as $fixture) {
            $assert(!in_array($fixture['contract'],$ids,true),'Ineligible contract offered for creation.');
            try { $service->creationContract($fixture['contract']); throw new RuntimeException('Direct creation accepted an ineligible contract.'); }
            catch (BusinessRuleException) {}
        }
        try { $service->selectCreationContract(new StatementContractSelectionData($active['project'],$boq['contract'])); throw new RuntimeException('Cross-project selection accepted.'); }
        catch (BusinessRuleException) {}
        $validator = new StatementContractSelectionValidator();
        foreach ([[],['project_id'=>'0','contract_id'=>'1'],['project_id'=>'1','contract_id'=>['1']]] as $invalid) {
            try { $validator->validate($invalid); throw new RuntimeException('Invalid selection IDs accepted.'); }
            catch (ValidationException) {}
        }
        $page = $controller->global(new Request())->content();
        $assert(str_contains($page,'href="/client-progress-statements/create"'),'Global create button missing.');
        $response = $controller->selectContract(new Request(['project_id'=>$active['project'],'contract_id'=>$active['contract']]));
        $headers = (new ReflectionProperty($response,'headers'))->getValue($response);
        $assert($response->status()===302 && $headers['Location']==='/client-contracts/'.$active['contract'].'/progress-statements/create','Selection did not redirect to the existing form.');
        $response = $controller->selectContract(new Request(['project_id'=>$active['project'],'contract_id'=>$draft['contract']]));
        $assert($response->status()===422 && str_contains($response->content(),'cps-contract-selection'),'Invalid selection did not return to the picker.');
        $count = $db->execute('SELECT COUNT(*) total FROM client_progress_statements WHERE client_id=?',[$clientId])->fetch_assoc();
        $assert((int)$count['total']===0,'Selection created a statement before saving the form.');
        $previewRequest=new Request(['statement_date'=>'2026-10-29']);
        $previewRequest->setRouteParams(['contract_id'=>(string)$active['contract']]);
        $preview=$controller->preview($previewRequest);
        $assert($preview->status()===200 && isset(json_decode($preview->content(),true)['scopeHtml']),'Dated Cost Plus preview failed.');
        $invalidPreview=new Request(['statement_date'=>'invalid']);
        $invalidPreview->setRouteParams(['contract_id'=>(string)$active['contract']]);
        $assert($controller->preview($invalidPreview)->status()===422,'Preview accepted an invalid date.');

        $db->execute('INSERT INTO roles(name,code) VALUES(?,?)',['CPS Viewer','cps-viewer-'.$suffix]);
        $roleId=(int)$db->connection()->insert_id;
        $permission=$db->execute("SELECT id FROM permissions WHERE code='client_progress_statements.view'")->fetch_assoc();
        $db->execute('INSERT INTO role_permissions(role_id,permission_id) VALUES(?,?)',[$roleId,(int)$permission['id']]);
        $db->execute('DELETE FROM user_roles WHERE user_id=?',[$userId]);
        $db->execute('INSERT INTO user_roles(user_id,role_id) VALUES(?,?)',[$userId,$roleId]);
        $assert(!str_contains($controller->global(new Request())->content(),'href="/client-progress-statements/create"'),'Viewer sees create button.');
        $response=$app->handle(new Request([],[],['REQUEST_METHOD'=>'GET','REQUEST_URI'=>'/client-progress-statements/create']));
        $assert($response->status()===403,'Create route bypassed creation permission or matched the statement details route.');
        $assert($controller->preview($previewRequest)->status()===403,'Read-only viewer accessed the editing preview endpoint.');
    } finally {
        foreach ($contracts as $id) {
            $db->execute('DELETE FROM contract_boqs WHERE client_contract_id=?',[$id]);
            $db->execute('DELETE FROM client_contracts WHERE id=?',[$id]);
        }
        foreach ($projects as $id) $db->execute('DELETE FROM projects WHERE id=?',[$id]);
        if ($clientId!==null) $db->execute('DELETE FROM clients WHERE id=?',[$clientId]);
        if ($userId!==null) { $db->execute('DELETE FROM user_roles WHERE user_id=?',[$userId]); $db->execute('DELETE FROM users WHERE id=?',[$userId]); }
        if ($roleId!==null) { $db->execute('DELETE FROM role_permissions WHERE role_id=?',[$roleId]); $db->execute('DELETE FROM roles WHERE id=?',[$roleId]); }
        if ($previousUser!==null) $auth->refresh($previousUser); else $auth->logout();
    }
})();
