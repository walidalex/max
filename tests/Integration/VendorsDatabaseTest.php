<?php
declare(strict_types=1);
use App\Core\Database\Database;
use App\Modules\Vendors\Services\VendorService;
use App\Modules\Vendors\Validators\VendorTableQueryValidator;
/** @var Database $database */$database=$app->make(Database::class);
$result=$database->execute("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('vendors','vendor_contacts','vendor_work_sections')");
$tables=$result instanceof mysqli_result?array_column($result->fetch_all(MYSQLI_ASSOC),'TABLE_NAME'):[];sort($tables);
if($tables!==['vendor_contacts','vendor_work_sections','vendors']){throw new RuntimeException('Vendor database tables are missing.');}
$sequence=$database->execute('SELECT current_value FROM number_sequences WHERE sequence_key=? LIMIT 1',['vendors']);
if(!($sequence instanceof mysqli_result)||$sequence->num_rows!==1){throw new RuntimeException('Vendor number sequence is missing.');}
/** @var VendorService $service */$service=$app->make(VendorService::class);
$table=$service->dataTable((new VendorTableQueryValidator())->validate(['draw'=>'1','start'=>'0','length'=>'10','search'=>['value'=>''],'order'=>[['column'=>'0','dir'=>'asc']]]));
if($table['draw']!==1||!is_array($table['data'])){throw new RuntimeException('Server-side vendors query failed.');}
