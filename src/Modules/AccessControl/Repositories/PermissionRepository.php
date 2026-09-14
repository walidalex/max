<?php
declare(strict_types=1);
namespace App\Modules\AccessControl\Repositories;
use App\Core\Database\Database;
final class PermissionRepository
{
    public function __construct(private readonly Database $db) {}
    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        $result = $this->db->execute('SELECT id,name,code,module,description,is_system FROM permissions ORDER BY module,code');
        return $result instanceof \mysqli_result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    /** @param list<int> $ids @return list<int> */
    public function validIds(array $ids): array
    {
        if($ids===[]){return [];}$marks=implode(',',array_fill(0,count($ids),'?'));
        $result=$this->db->execute("SELECT id FROM permissions WHERE id IN ({$marks})",$ids);
        return $result instanceof \mysqli_result?array_map('intval',array_column($result->fetch_all(MYSQLI_ASSOC),'id')):[];
    }
}
