<?php
declare(strict_types=1);
use App\Core\Database\Database;use App\Modules\Projects\Services\ProjectService;use App\Modules\Projects\Validators\ProjectTableQueryValidator;use App\Shared\Numbering\NumberGeneratorService;
/** @var Database $database */$database=$app->make(Database::class);
$r=$database->execute("SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='projects'");if(!($r instanceof mysqli_result)||$r->num_rows!==1)throw new RuntimeException('Projects table missing.');
$r=$database->execute("SELECT COUNT(*) total FROM permissions WHERE code LIKE 'projects.%'");$row=$r instanceof mysqli_result?$r->fetch_assoc():[];if((int)($row['total']??0)!==4)throw new RuntimeException('Project permissions missing.');
$r=$database->execute("SELECT COUNT(*) total FROM role_permissions rp JOIN roles r ON r.id=rp.role_id JOIN permissions p ON p.id=rp.permission_id WHERE p.code LIKE 'projects.%' AND r.code<>'super_admin'");$row=$r instanceof mysqli_result?$r->fetch_assoc():[];if((int)($row['total']??0)!==0)throw new RuntimeException('Project permissions assigned to wrong role.');
/** @var NumberGeneratorService $numbers */$numbers=$app->make(NumberGeneratorService::class);$year=(int)date('Y');if(!preg_match('/^PRJ-'.$year.'-\d{4,}$/',$numbers->nextProjectCode($year)))throw new RuntimeException('Project numbering failed.');
/** @var ProjectService $service */$service=$app->make(ProjectService::class);$result=$service->dataTable((new ProjectTableQueryValidator())->validate(['draw'=>1,'start'=>0,'length'=>10,'order'=>[['column'=>0,'dir'=>'asc']]]));if($result['draw']!==1||!is_array($result['data']))throw new RuntimeException('Projects DataTable failed.');
