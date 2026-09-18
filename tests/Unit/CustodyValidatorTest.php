<?php
declare(strict_types=1);
use App\Core\Exceptions\ValidationException;
use App\Modules\EmployeeCustodies\Validators\CustodyValidator;
use App\Modules\EmployeeCustodies\Validators\SettlementValidator;
$data=(new CustodyValidator())->validate(['employee_id'=>1,'issue_date'=>'2026-01-01','amount'=>'9999999999999999.99','funding_type'=>'cash','funding_account_id'=>1]);
if($data->amount!=='9999999999999999.99')throw new RuntimeException('Maximum custody amount rejected.');
try{(new CustodyValidator())->validate(['employee_id'=>1,'issue_date'=>'2026-01-01','amount'=>'10000000000000000.00','funding_type'=>'cash','funding_account_id'=>1]);throw new RuntimeException('Oversized custody accepted.');}catch(ValidationException){}
$settlement=(new SettlementValidator())->validate(['settlement_date'=>'2026-01-02','description'=>'Expense','amount'=>'0.20','project_id'=>1,'cost_code_id'=>1],'expense');
if($settlement->amount!=='0.20'||$settlement->returnAccountId!==null)throw new RuntimeException('Expense settlement validation failed.');
