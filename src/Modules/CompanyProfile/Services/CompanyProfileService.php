<?php
declare(strict_types=1);
namespace App\Modules\CompanyProfile\Services;
use App\Core\Exceptions\BusinessRuleException;
use App\Modules\CompanyProfile\DTOs\CompanyProfileData;
use App\Modules\CompanyProfile\Repositories\CompanyProfileRepository;
final class CompanyProfileService
{
    public function __construct(private readonly CompanyProfileRepository $profiles,private readonly CompanyLogoService $logos) {}
    /** @return array<string,mixed> */
    public function get():array{return $this->profiles->get()??throw new BusinessRuleException('ملف الشركة غير مهيأ. شغّل migrations أولًا.');}
    public function update(CompanyProfileData $data):void
    {
        $current=$this->get();$old=$current['logo_path']!==null?(string)$current['logo_path']:null;$new=null;
        try{if($data->logo!==null){$new=$this->logos->store($data->logo);}$this->profiles->update($data,$new??$old);}
        catch(\Throwable $e){if($new!==null){$this->logos->delete($new);}throw $e;}
        if($new!==null){$this->logos->delete($old);}
    }
    /** @return array{path:string,mime:string} */
    public function logo():array
    {
        $path=$this->get()['logo_path']??null;if(!is_string($path)||$path===''){throw new BusinessRuleException('لا يوجد شعار للشركة.');}
        return $this->logos->resolve($path);
    }
    public function logoDataUri():?string
    {
        try{$logo=$this->logo();$contents=file_get_contents($logo['path']);}
        catch(BusinessRuleException){return null;}
        if($contents===false){return null;}
        return 'data:'.$logo['mime'].';base64,'.base64_encode($contents);
    }
}
