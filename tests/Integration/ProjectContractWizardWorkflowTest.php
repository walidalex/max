<?php

declare(strict_types=1);

use App\Core\Auth\Auth;
use App\Core\Database\Database;
use App\Core\Exceptions\BusinessRuleException;
use App\Core\Exceptions\ValidationException;
use App\Core\Http\Csrf;
use App\Core\Http\Request;
use App\Modules\ClientContracts\Controllers\ProjectContractWizardController;
use App\Modules\ClientContracts\Controllers\ProjectContractDirectoryController;
use App\Modules\ClientContracts\Services\ProjectContractWizardService;
use App\Modules\ClientContracts\Validators\ProjectContractWizardValidator;

(static function () use ($app): void {
    $db = $app->make(Database::class);
    $auth = $app->make(Auth::class);
    $validator = $app->make(ProjectContractWizardValidator::class);
    $service = $app->make(ProjectContractWizardService::class);
    $controller = $app->make(ProjectContractWizardController::class);
    $previousUser = $auth->user();
    $suffix = bin2hex(random_bytes(5));
    $projectIds = [];
    $clientId = $userId = $limitedRoleId = null;
    $assert = static function (bool $condition, string $message): void {
        if (!$condition) throw new RuntimeException($message);
    };
    try {
        $db->execute('INSERT INTO users(username,name,password_hash) VALUES(?,?,?)', ['wizard-'.$suffix, 'Wizard Test', password_hash($suffix, PASSWORD_DEFAULT)]);
        $userId = (int) $db->connection()->insert_id;
        $role = $db->execute("SELECT id FROM roles WHERE code='super_admin'")->fetch_assoc();
        $db->execute('INSERT INTO user_roles(user_id,role_id) VALUES(?,?)', [$userId, (int) $role['id']]);
        $auth->refresh(['id'=>$userId, 'name'=>'Wizard Test', 'username'=>'wizard-'.$suffix]);
        $db->execute("INSERT INTO clients(client_code,client_type,name) VALUES(?,'individual',?)", ['WIZ-'.$suffix, 'Wizard Client']);
        $clientId = (int) $db->connection()->insert_id;
        $project = ['name'=>'Wizard '.$suffix, 'client_id'=>(string)$clientId, 'project_type'=>'contracting'];
        $contract = ['title'=>'Wizard Contract', 'client_id'=>(string)$clientId, 'pricing_method'=>'lump_sum', 'contract_date'=>'2026-10-29', 'contract_value'=>'1000'];
        $input = ['project'=>$project, 'contract'=>$contract];

        // The inner project save must never survive a failed contract save.
        try {
            $service->save($validator->validate([...$input, 'project'=>[...$project, 'name'=>'Rollback '.$suffix], 'contract'=>[...$contract, 'client_contact_id'=>'2147483647']]));
            throw new RuntimeException('Invalid contract contact was accepted.');
        } catch (BusinessRuleException) {}
        $count = $db->execute('SELECT COUNT(*) total FROM projects WHERE name=?', ['Rollback '.$suffix])->fetch_assoc();
        $assert((int)$count['total'] === 0, 'Failed contract left a committed project.');

        $result = $service->save($validator->validate($input));
        $projectIds[] = $result['projectId'];
        $saved = $db->execute('SELECT p.status project_status,cc.status contract_status,cc.client_id,cc.contract_value FROM projects p JOIN client_contracts cc ON cc.project_id=p.id WHERE p.id=?', [$result['projectId']])->fetch_assoc();
        $assert($saved && $saved['project_status']==='planning' && $saved['contract_status']==='draft' && (int)$saved['client_id']===$clientId && $saved['contract_value']==='1000.00', 'Wizard did not save project and draft contract together.');

        // Legacy/tampered mode and ID fields cannot switch to an existing project or skip its contract.
        $alwaysNew = [...$input, 'mode'=>'existing', 'with_contract'=>'0', 'project_id'=>(string)$result['projectId'], 'contract'=>[...$contract, 'client_id'=>'2147483647']];
        $attached = $service->save($validator->validate($alwaysNew));
        $projectIds[] = $attached['projectId'];
        $saved = $db->execute('SELECT client_id FROM client_contracts WHERE id=?', [$attached['contractId']])->fetch_assoc();
        $assert($attached['projectId'] !== $result['projectId'] && $attached['contractId'] !== null && (int)$saved['client_id'] === $clientId, 'Wizard did not enforce a new project with its own contract and derived client.');
        $number = $db->execute('SELECT contract_number FROM client_contracts WHERE id=?', [$attached['contractId']])->fetch_assoc()['contract_number'];
        $projectService = $app->make(App\Modules\Projects\Services\ProjectService::class);
        $query = (new App\Modules\Projects\Validators\ProjectTableQueryValidator())->validate(['search'=>['value'=>$number]]);
        $assert($projectService->dataTable($query, true)['recordsFiltered']===1, 'Unified directory cannot search by contract number.');
        $assert($projectService->dataTable($query, false)['recordsFiltered']===0, 'Project-only search disclosed a contract number.');

        // A caught nested failure rolls back only its savepoint, then the outer save continues.
        $db->transaction(function () use ($db, $clientId, $assert): void {
            $db->execute('UPDATE clients SET name=? WHERE id=?', ['Outer', $clientId]);
            try {
                $db->transaction(function () use ($db, $clientId): void {
                    $db->execute('UPDATE clients SET name=? WHERE id=?', ['Inner', $clientId]);
                    throw new LogicException('Rollback nested work');
                });
            } catch (LogicException) {}
            $row = $db->execute('SELECT name FROM clients WHERE id=?', [$clientId])->fetch_assoc();
            $assert($row['name']==='Outer', 'Savepoint did not isolate the nested rollback.');
        });

        $response = $controller->create(new Request());
        $assert($response->status() === 200 && str_contains($response->content(), 'project-contract-wizard') && str_contains($response->content(), 'المراجعة والحفظ'), 'Wizard page failed to render.');
        $directory = $app->make(ProjectContractDirectoryController::class)->index(new Request())->content();
        $assert(!str_contains($directory, 'role="tablist"') && !str_contains($directory, 'id="contracts-table"') && str_contains($directory, '/project-contracts/create'), 'Directory still renders duplicate tabs or tables.');
        $assert($controller->store(new Request([], $input))->status()===419, 'Wizard accepted an invalid CSRF token.');
        $invalid = [...$input, '_token'=>$app->make(Csrf::class)->token(), 'contract'=>[...$contract, 'title'=>'<script>test</script>', 'contract_date'=>'bad']];
        $response = $controller->store(new Request([], $invalid));
        $assert($response->status()===422 && str_contains($response->content(), '&lt;script&gt;test&lt;/script&gt;') && str_contains($response->content(), e($project['name'])), 'Validation response lost or failed to escape submitted values.');
        foreach ([['project'=>$project], ['project_id'=>$result['projectId'], 'contract'=>$contract], [...$input, 'contract'=>[...$contract, 'pricing_method'=>'cost_plus', 'contract_value'=>'']]] as $invalidInput) {
            try {
                $validator->validate($invalidInput);
                throw new RuntimeException('Invalid wizard data accepted.');
            } catch (ValidationException) {}
        }

        // Neither creation permission alone can open or save the combined wizard.
        $db->execute('INSERT INTO roles(name,code) VALUES(?,?)', ['Wizard Limited', 'wizard-limited-'.$suffix]);
        $limitedRoleId = (int)$db->connection()->insert_id;
        $db->execute('DELETE FROM user_roles WHERE user_id=?', [$userId]);
        $db->execute('INSERT INTO user_roles(user_id,role_id) VALUES(?,?)', [$userId,$limitedRoleId]);
        foreach (['projects.create','client_contracts.create'] as $permission) {
            $db->execute('DELETE FROM role_permissions WHERE role_id=?', [$limitedRoleId]);
            $permissionRow = $db->execute('SELECT id FROM permissions WHERE code=?', [$permission])->fetch_assoc();
            $db->execute('INSERT INTO role_permissions(role_id,permission_id) VALUES(?,?)', [$limitedRoleId,(int)$permissionRow['id']]);
            $assert($controller->create(new Request())->status()===403, 'One creation permission incorrectly allowed the combined wizard.');
            try {
                $service->save($validator->validate($input));
                throw new RuntimeException('Service accepted incomplete creation permissions.');
            } catch (BusinessRuleException) {}
        }
        $auth->refresh(['id'=>2147483647]);
        $assert($controller->create(new Request())->status()===403, 'Wizard allowed a user without creation permissions.');
        $assert($controller->contacts(new Request(['mode'=>'new','id'=>$clientId]))->status()===403, 'Contact endpoint bypassed authorization.');
        try {
            $service->save($validator->validate($input));
            throw new RuntimeException('Service bypassed project creation authorization.');
        } catch (BusinessRuleException) {}
        try {
            $service->save($validator->validate($alwaysNew));
            throw new RuntimeException('Service bypassed creation authorization.');
        } catch (BusinessRuleException) {}
    } finally {
        foreach ($projectIds as $projectId) {
            $db->execute('DELETE FROM client_contracts WHERE project_id=?', [$projectId]);
            $db->execute('DELETE FROM projects WHERE id=?', [$projectId]);
        }
        if ($clientId !== null) $db->execute('DELETE FROM clients WHERE id=?', [$clientId]);
        if ($userId !== null) {
            $db->execute('DELETE FROM user_roles WHERE user_id=?', [$userId]);
            $db->execute('DELETE FROM users WHERE id=?', [$userId]);
        }
        if ($limitedRoleId !== null) {
            $db->execute('DELETE FROM role_permissions WHERE role_id=?', [$limitedRoleId]);
            $db->execute('DELETE FROM roles WHERE id=?', [$limitedRoleId]);
        }
        if ($previousUser !== null) $auth->refresh($previousUser); else $auth->logout();
    }
})();
