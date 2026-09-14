<?php
declare(strict_types=1);
use App\Core\Exceptions\ValidationException;
use App\Core\Validation\Validator;
use App\Modules\CostStructure\Validators\CostCodeValidator;
use App\Modules\CostStructure\Validators\UnitValidator;
use App\Modules\CostStructure\Validators\WorkSectionValidator;
$section=(new WorkSectionValidator(new Validator()))->validate(['section_code'=>'20','name'=>'أعمال تجريبية','description'=>'','sort_order'=>'16']);
$unit=(new UnitValidator(new Validator()))->validate(['code'=>'kg','name_ar'=>'كيلوجرام','symbol_ar'=>'كجم','sort_order'=>'7']);
$code=(new CostCodeValidator(new Validator()))->validate(['work_section_id'=>'1','cost_code'=>'2001','name'=>'بند تجريبي','default_unit_id'=>'','description'=>'','sort_order'=>'1']);
if($section->description!==null||$unit->symbolAr!=='كجم'||$code->defaultUnitId!==null)throw new RuntimeException('Cost structure normalization failed.');
try{(new CostCodeValidator(new Validator()))->validate(['work_section_id'=>'0','cost_code'=>'','name'=>'']);throw new RuntimeException('Invalid cost code accepted.');}catch(ValidationException){}
