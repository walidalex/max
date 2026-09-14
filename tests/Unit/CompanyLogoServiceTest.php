<?php
declare(strict_types=1);
use App\Core\Exceptions\BusinessRuleException;
use App\Core\Http\UploadedFile;
use App\Modules\CompanyProfile\Services\CompanyLogoService;

$temporary=tempnam(sys_get_temp_dir(),'company-logo-');
file_put_contents($temporary,base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));
$logoService=new CompanyLogoService($app->config());
$relative=$logoService->store(new UploadedFile($temporary,'untrusted.php',UPLOAD_ERR_OK,(int)filesize($temporary)));
$resolved=$logoService->resolve($relative);
if(!str_ends_with($relative,'.png')||$resolved['mime']!=='image/png'){throw new RuntimeException('Logo MIME extension mapping failed.');}
$logoService->delete($relative);
try{$logoService->resolve('../outside.png');throw new RuntimeException('Unsafe logo path was accepted.');}
catch(BusinessRuleException){}
