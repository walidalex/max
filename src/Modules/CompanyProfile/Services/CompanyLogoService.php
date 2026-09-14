<?php
declare(strict_types=1);
namespace App\Modules\CompanyProfile\Services;
use App\Core\Config;
use App\Core\Exceptions\BusinessRuleException;
use App\Core\Http\UploadedFile;
final class CompanyLogoService
{
    private const EXTENSIONS=['image/png'=>'png','image/jpeg'=>'jpg','image/webp'=>'webp'];
    public function __construct(private readonly Config $config) {}
    public function store(UploadedFile $file):string
    {
        $mime=$file->mimeType();$extension=self::EXTENSIONS[$mime??'']??null;
        if($extension===null){throw new BusinessRuleException('صيغة الشعار غير مدعومة.');}
        $directory=$this->companyDirectory();
        if(!is_dir($directory)&&!mkdir($directory,0750,true)&&!is_dir($directory)){throw new \RuntimeException('Unable to create company upload directory.');}
        $name='logo-'.bin2hex(random_bytes(20)).'.'.$extension;
        $file->moveTo($directory.DIRECTORY_SEPARATOR.$name);
        return 'company/'.$name;
    }
    /** @return array{path:string,mime:string} */
    public function resolve(string $relativePath):array
    {
        if(!preg_match('#^company/logo-[a-f0-9]{40}\.(png|jpg|webp)$#',$relativePath)){throw new BusinessRuleException('الشعار غير موجود.');}
        $base=realpath($this->companyDirectory());$path=realpath($this->uploadsPath().DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$relativePath));
        if($base===false||$path===false||!str_starts_with($path,$base.DIRECTORY_SEPARATOR)||!is_file($path)){throw new BusinessRuleException('الشعار غير موجود.');}
        $mime=(new \finfo(FILEINFO_MIME_TYPE))->file($path);
        if(!is_string($mime)||!isset(self::EXTENSIONS[$mime])){throw new BusinessRuleException('ملف الشعار غير صالح.');}
        return ['path'=>$path,'mime'=>$mime];
    }
    public function delete(?string $relativePath):void
    {
        if($relativePath===null){return;}
        try{$file=$this->resolve($relativePath);if(is_file($file['path'])){@unlink($file['path']);}}catch(BusinessRuleException){}
    }
    private function uploadsPath():string{return rtrim((string)$this->config->get('app.storage_path'),'/\\').DIRECTORY_SEPARATOR.'uploads';}
    private function companyDirectory():string{return $this->uploadsPath().DIRECTORY_SEPARATOR.'company';}
}
