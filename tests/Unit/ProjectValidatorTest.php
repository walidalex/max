<?php
declare(strict_types=1);
use App\Core\Exceptions\ValidationException;use App\Modules\Projects\Validators\ProjectValidator;
/** @var ProjectValidator $validator */$validator=$app->make(ProjectValidator::class);
$data=$validator->validate(['name'=>'مشروع اختبار','client_id'=>'1','project_type'=>'fit_out','status'=>'planning','start_date'=>'2026-01-01','expected_end_date'=>'2026-02-01']);
if($data->name!=='مشروع اختبار'||$data->siteAddress!==null)throw new RuntimeException('Project validation failed.');
try{$validator->validate(['name'=>'اختبار','client_id'=>'1','project_type'=>'invalid','status'=>'planning','start_date'=>'2026-02-01','expected_end_date'=>'2026-01-01']);throw new RuntimeException('Invalid project data accepted.');}catch(ValidationException){}
