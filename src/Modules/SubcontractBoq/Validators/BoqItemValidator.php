<?php
declare(strict_types=1);
namespace App\Modules\SubcontractBoq\Validators;
use App\Core\Exceptions\ValidationException;
use App\Core\Validation\Validator;
use App\Modules\SubcontractBoq\DTOs\BoqItemData;
final class BoqItemValidator
{
    public function __construct(private readonly Validator $v) {}
    public function validate(array $i): BoqItemData
    {
        if (!$this->v->validate($i, ['description'=>'required|string|max:1000','item_code'=>'string|max:50','notes'=>'string|max:5000'])) throw new ValidationException($this->v->errors());
        $section=$this->id($i['section_id']??null,false);$cost=$this->id($i['cost_code_id']??null,true);$unit=$this->id($i['unit_id']??null,true);$type=(string)($i['pricing_type']??'quantity');$sort=filter_var($i['sort_order']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);$q=$this->decimal($i['quantity']??null,4);$rate=$this->decimal($i['unit_rate']??null,2);$lump=$this->decimal($i['lump_sum_amount']??null,2);$e=[];
        if($section===null)$e['section_id'][]='القسم غير صالح.';if(!in_array($type,['quantity','lump_sum'],true))$e['pricing_type'][]='نوع تسعير البند غير صالح.';if($sort===false)$e['sort_order'][]='الترتيب يجب أن يكون رقماً موجباً.';if($type==='quantity'){if($unit===null)$e['unit_id'][]='الوحدة مطلوبة للبند المسعّر بالكمية.';if($q===null)$e['quantity'][]='الكمية مطلوبة للبند المسعّر بالكمية.';if($rate===null)$e['unit_rate'][]='سعر الوحدة مطلوب للبند المسعّر بالكمية.';}elseif($lump===null)$e['lump_sum_amount'][]='قيمة البند المقطوعي مطلوبة.';
        foreach(['quantity'=>$q,'unit_rate'=>$rate,'lump_sum_amount'=>$lump] as $f=>$x)if(trim((string)($i[$f]??''))!==''&&$x===null)$e[$f][]='القيمة يجب أن تكون رقماً لا يقل عن صفر.';if($e)throw new ValidationException($e);
        return new BoqItemData((int)$section,$cost,$this->n($i['item_code']??null),trim((string)$i['description']),$type,$unit,$q,$rate,$lump,(int)$sort,$this->n($i['notes']??null));
    }
    private function id(mixed $v,bool $optional):?int{if($optional&&trim((string)$v)==='')return null;$x=filter_var($v,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);return$x===false?null:$x;}
    private function decimal(mixed $v,int $scale):?string{$v=trim((string)$v);if($v===''||!preg_match('/^\d+(?:\.\d+)?$/',$v))return null;[$integer,$fraction]=array_pad(explode('.',$v,2),2,'');$integer=ltrim($integer,'0')?:'0';return $integer.'.'.str_pad(substr($fraction,0,$scale),$scale,'0');}
    private function n(mixed $v):?string{$v=trim((string)$v);return$v===''?null:$v;}
}
