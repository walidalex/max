<?php
declare(strict_types=1);
namespace App\Modules\AccessControl\Repositories;
use App\Core\Database\Database;
final class RoleRepository
{
    public function __construct(private readonly Database $db) {}
    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        $result = $this->db->execute('SELECT r.id,r.name,r.code,r.description,r.is_active,r.is_system,COUNT(DISTINCT ur.user_id) users_count,COUNT(DISTINCT rp.permission_id) permissions_count FROM roles r LEFT JOIN user_roles ur ON ur.role_id=r.id LEFT JOIN role_permissions rp ON rp.role_id=r.id GROUP BY r.id ORDER BY r.is_system DESC,r.name');
        return $result instanceof \mysqli_result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $result = $this->db->execute('SELECT id,name,code,description,is_active,is_system FROM roles WHERE id=? LIMIT 1', [$id]);
        $row = $result instanceof \mysqli_result ? $result->fetch_assoc() : null;
        return is_array($row) ? $row : null;
    }
    public function codeExists(string $code, ?int $id = null): bool
    {
        $sql = 'SELECT 1 FROM roles WHERE code=?' . ($id === null ? '' : ' AND id<>?') . ' LIMIT 1';
        $result = $this->db->execute($sql, $id === null ? [$code] : [$code, $id]);
        return $result instanceof \mysqli_result && $result->num_rows > 0;
    }
    public function create(string $name, string $code, ?string $description): int
    {
        $this->db->execute('INSERT INTO roles(name,code,description) VALUES(?,?,?)', [$name, $code, $description]);
        return (int) $this->db->connection()->insert_id;
    }
    public function update(int $id, string $name, string $code, ?string $description, bool $active): void
    {
        $this->db->execute('UPDATE roles SET name=?,code=?,description=?,is_active=? WHERE id=? AND is_system=0', [$name, $code, $description, $active, $id]);
    }
    /** @return list<int> */
    public function permissionIds(int $id): array
    {
        $result = $this->db->execute('SELECT permission_id FROM role_permissions WHERE role_id=?', [$id]);
        return $result instanceof \mysqli_result ? array_map('intval', array_column($result->fetch_all(MYSQLI_ASSOC), 'permission_id')) : [];
    }
    /** @param list<int> $ids */
    public function syncPermissions(int $roleId, array $ids): void
    {
        $this->db->execute('DELETE FROM role_permissions WHERE role_id=?', [$roleId]);
        foreach (array_unique($ids) as $id) { $this->db->execute('INSERT INTO role_permissions(role_id,permission_id) VALUES(?,?)', [$roleId, $id]); }
    }
    /** @param list<int> $ids @return list<int> */
    public function validActiveIds(array $ids): array
    {
        if($ids===[]){return [];}$marks=implode(',',array_fill(0,count($ids),'?'));
        $result=$this->db->execute("SELECT id FROM roles WHERE is_active=1 AND id IN ({$marks})",$ids);
        return $result instanceof \mysqli_result?array_map('intval',array_column($result->fetch_all(MYSQLI_ASSOC),'id')):[];
    }
}
