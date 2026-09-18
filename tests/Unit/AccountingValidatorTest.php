<?php
declare(strict_types=1);
use App\Core\Exceptions\ValidationException;
use App\Modules\Accounting\Validators\AccountValidator;
use App\Modules\Accounting\Validators\JournalValidator;
$account=(new AccountValidator())->validate(['account_code'=>'123456','name_ar'=>'اختبار','name_en'=>'Test','account_type'=>'asset','normal_balance'=>'debit','is_postable'=>'1','is_active'=>'1']);
if($account->code!=='123456'||!$account->isPostable)throw new RuntimeException('Account validation failed.');
foreach(['12345','1234567','12A456']as$code){try{(new AccountValidator())->validate(['account_code'=>$code,'name_ar'=>'اختبار','name_en'=>'Test','account_type'=>'asset','normal_balance'=>'debit']);throw new RuntimeException('Invalid account code accepted.');}catch(ValidationException){}}
$journal=(new JournalValidator())->validate(['journal_date'=>'2026-01-01','description'=>'Exact decimal','lines'=>[['account_id'=>1,'description'=>'D','debit'=>'0.10','credit'=>'0'],['account_id'=>2,'description'=>'C','debit'=>'0','credit'=>'0.10']]]);
if($journal->lines[0]['debit']!=='0.10'||$journal->lines[1]['credit']!=='0.10')throw new RuntimeException('Journal decimals lost precision.');
try{(new JournalValidator())->validate(['journal_date'=>'2026-01-01','description'=>'Both sides','lines'=>[['account_id'=>1,'description'=>'X','debit'=>'1','credit'=>'1'],['account_id'=>2,'description'=>'Y','debit'=>'0','credit'=>'1']]]);throw new RuntimeException('Two-sided line accepted.');}catch(ValidationException){}
