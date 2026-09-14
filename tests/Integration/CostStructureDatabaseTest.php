<?php
declare(strict_types=1);
use App\Core\Database\Database;
/** @var Database $db */$db=$app->make(Database::class);
foreach(['units'=>6,'work_sections'=>15,'cost_codes'=>94] as $table=>$expected){$result=$db->execute("SELECT COUNT(*) total FROM {$table}");$row=$result instanceof mysqli_result?$result->fetch_assoc():[];if((int)($row['total']??0)!==$expected)throw new RuntimeException("Unexpected {$table} seed count.");}
$result=$db->execute("SELECT COUNT(*) total FROM work_sections WHERE section_code IN ('11','12','14','17')");$row=$result instanceof mysqli_result?$result->fetch_assoc():[];if((int)($row['total']??0)!==0)throw new RuntimeException('Missing section numbers were created.');
$result=$db->execute("SELECT COUNT(*) total FROM role_permissions rp JOIN roles r ON r.id=rp.role_id JOIN permissions p ON p.id=rp.permission_id WHERE r.code='super_admin' AND p.code IN ('cost_structure.view','cost_structure.manage')");$row=$result instanceof mysqli_result?$result->fetch_assoc():[];if((int)($row['total']??0)!==2)throw new RuntimeException('Cost structure permissions are not assigned.');
foreach(glob(dirname(__DIR__,2).'/database/migrations/0{32,33,34,35,36}_*.sql',GLOB_BRACE)?:[] as $file){$sql=(string)file_get_contents($file);if(stripos($sql,'INSERT IGNORE')!==false||stripos($sql,'ON DUPLICATE KEY UPDATE')===false)throw new RuntimeException('Seeder idempotency policy failed: '.basename($file));}
