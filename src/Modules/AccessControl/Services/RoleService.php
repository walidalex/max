<?php
declare(strict_types=1);
namespace App\Modules\AccessControl\Services;
use App\Core\Database\Database;
use App\Core\Exceptions\ValidationException;
use App\Core\Exceptions\BusinessRuleException;
use App\Modules\AccessControl\DTOs\RoleData;
use App\Modules\AccessControl\Repositories\RoleRepository;
use App\Modules\AccessControl\Repositories\PermissionRepository;
final class RoleService
{
    public function __construct(private readonly RoleRepository $roles,private readonly PermissionRepository $permissions,private readonly Database $db) {}
    /** @return list<array<string,mixed>> */ public function all():array{return $this->roles->all();}
    /** @return array<string,mixed> */ public function find(int $id):array{return $this->roles->find($id)??throw new BusinessRuleException('الدور غير موجود.');}
    public function save(RoleData $data,?int $id=null):int
    {
        if($this->roles->codeExists($data->code,$id)){throw new ValidationException(['code'=>['كود الدور مستخدم بالفعل.']]);}
        if($id!==null&&(bool)$this->find($id)['is_system']){throw new BusinessRuleException('لا يمكن تعديل دور نظام محمي.');}
        if($id===null){return $this->roles->create($data->name,$data->code,$data->description);}
        $this->roles->update($id,$data->name,$data->code,$data->description,$data->isActive);return $id;
    }
    /** @param list<int> $ids */
    public function assignPermissions(int $id,array $ids):void
    {
        if((bool)$this->find($id)['is_system']){throw new BusinessRuleException('صلاحيات دور مدير النظام محمية وتُدار بواسطة النظام.');}
        if(count($this->permissions->validIds($ids))!==count($ids)){throw new BusinessRuleException('تم اختيار صلاحية غير صالحة.');}
        $this->db->transaction(fn()=>$this->roles->syncPermissions($id,$ids));
    }
    /** @return list<int> */ public function permissionIds(int $id):array{return $this->roles->permissionIds($id);}
}
