<?php
declare(strict_types=1);
namespace App\Modules\AccessControl\Services;
use App\Core\Database\Database;
use App\Core\Exceptions\ValidationException;
use App\Core\Exceptions\BusinessRuleException;
use App\Modules\AccessControl\DTOs\UserData;
use App\Modules\AccessControl\Repositories\UserRepository;
use App\Modules\AccessControl\Repositories\RoleRepository;
final class UserService
{
    public function __construct(private readonly UserRepository $users,private readonly RoleRepository $roles,private readonly Database $db) {}
    /** @return list<array<string,mixed>> */ public function all():array{return $this->users->all();}
    /** @return array<string,mixed> */ public function find(int $id):array{return $this->users->find($id)??throw new BusinessRuleException('المستخدم غير موجود.');}
    public function save(UserData $data,?int $id=null):int
    {
        $errors=[];
        if($this->users->usernameExists($data->username,$id)){$errors['username'][]='اسم المستخدم مستخدم بالفعل.';}
        if($data->email!==null&&$this->users->emailExists($data->email,$id)){$errors['email'][]='البريد الإلكتروني مستخدم بالفعل.';}
        if($errors!==[]){throw new ValidationException($errors);}
        return $this->db->transaction(function()use($data,$id):int{
            $hash=$data->password===null?null:password_hash($data->password,PASSWORD_DEFAULT);
            if($data->password!==null&&!is_string($hash)){throw new \RuntimeException('Password hashing failed.');}
            if($id===null){$id=$this->users->create($data->username,$data->name,$data->email,(string)$hash);}
            else{$this->users->update($id,$data->username,$data->name,$data->email,$hash);}
            return $id;
        });
    }
    /** @param list<int> $roleIds */
    public function assignRoles(int $id,array $roleIds):void
    {
        $user=$this->find($id); $systemRoleId=$this->users->systemRoleId();
        if(count($this->roles->validActiveIds($roleIds))!==count($roleIds)){throw new BusinessRuleException('تم اختيار دور غير صالح أو غير نشط.');}
        if((bool)$user['is_active']&&$this->users->hasSystemRole($id)&&$systemRoleId!==null&&!in_array($systemRoleId,$roleIds,true)&&$this->users->activeSystemRoleUserCount()<=1){throw new BusinessRuleException('لا يمكن إزالة دور النظام المحمي من آخر مدير نشط.');}
        $this->db->transaction(fn()=>$this->users->syncRoles($id,$roleIds));
    }
    public function setActive(int $id,bool $active,int $actorId):void
    {
        $user=$this->find($id);
        if(!$active&&$id===$actorId){throw new BusinessRuleException('لا يمكنك تعطيل حسابك الحالي.');}
        if(!$active&&(bool)$user['is_active']&&$this->users->hasSystemRole($id)&&$this->users->activeSystemRoleUserCount()<=1){throw new BusinessRuleException('لا يمكن تعطيل آخر مدير نظام نشط.');}
        $this->users->setActive($id,$active);
    }
    /** @return list<int> */ public function roleIds(int $id):array{return $this->users->roleIds($id);}
}
