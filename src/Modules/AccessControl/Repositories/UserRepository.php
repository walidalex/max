<?php
declare(strict_types=1);
namespace App\Modules\AccessControl\Repositories;
use App\Core\Database\Database;
final class UserRepository
{
    public function __construct(private readonly Database $db) {}
    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        $sql = "SELECT u.id,u.username,u.name,u.email,u.is_active,u.last_login_at,GROUP_CONCAT(r.name SEPARATOR '، ') role_names FROM users u LEFT JOIN user_roles ur ON ur.user_id=u.id LEFT JOIN roles r ON r.id=ur.role_id GROUP BY u.id ORDER BY u.id DESC";
        $result = $this->db->execute($sql);
        return $result instanceof \mysqli_result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $result = $this->db->execute('SELECT id,username,name,email,is_active,last_login_at FROM users WHERE id=? LIMIT 1', [$id]);
        $row = $result instanceof \mysqli_result ? $result->fetch_assoc() : null;
        return is_array($row) ? $row : null;
    }
    public function usernameExists(string $value, ?int $id = null): bool { return $this->exists('username', $value, $id); }
    public function emailExists(string $value, ?int $id = null): bool { return $this->exists('email', $value, $id); }
    private function exists(string $column, string $value, ?int $id): bool
    {
        $sql = "SELECT 1 FROM users WHERE {$column}=?" . ($id === null ? '' : ' AND id<>?') . ' LIMIT 1';
        $result = $this->db->execute($sql, $id === null ? [$value] : [$value, $id]);
        return $result instanceof \mysqli_result && $result->num_rows > 0;
    }
    public function create(string $username, string $name, ?string $email, string $hash): int
    {
        $this->db->execute('INSERT INTO users(username,name,email,password_hash) VALUES(?,?,?,?)', [$username, $name, $email, $hash]);
        return (int) $this->db->connection()->insert_id;
    }
    public function update(int $id, string $username, string $name, ?string $email, ?string $hash): void
    {
        if ($hash !== null) { $this->db->execute('UPDATE users SET username=?,name=?,email=?,password_hash=? WHERE id=?', [$username, $name, $email, $hash, $id]); return; }
        $this->db->execute('UPDATE users SET username=?,name=?,email=? WHERE id=?', [$username, $name, $email, $id]);
    }
    public function setActive(int $id, bool $active): void { $this->db->execute('UPDATE users SET is_active=? WHERE id=?', [$active, $id]); }
    /** @return list<int> */
    public function roleIds(int $id): array
    {
        $result = $this->db->execute('SELECT role_id FROM user_roles WHERE user_id=?', [$id]);
        return $result instanceof \mysqli_result ? array_map('intval', array_column($result->fetch_all(MYSQLI_ASSOC), 'role_id')) : [];
    }
    /** @param list<int> $roleIds */
    public function syncRoles(int $id, array $roleIds): void
    {
        $this->db->execute('DELETE FROM user_roles WHERE user_id=?', [$id]);
        foreach (array_unique($roleIds) as $roleId) { $this->db->execute('INSERT INTO user_roles(user_id,role_id) VALUES(?,?)', [$id, $roleId]); }
    }
    public function hasSystemRole(int $id): bool
    {
        $result = $this->db->execute('SELECT 1 FROM user_roles ur INNER JOIN roles r ON r.id=ur.role_id WHERE ur.user_id=? AND r.is_system=1 LIMIT 1', [$id]);
        return $result instanceof \mysqli_result && $result->num_rows > 0;
    }
    public function activeSystemRoleUserCount(): int
    {
        $result = $this->db->execute('SELECT COUNT(DISTINCT u.id) total FROM users u INNER JOIN user_roles ur ON ur.user_id=u.id INNER JOIN roles r ON r.id=ur.role_id WHERE u.is_active=1 AND r.is_system=1');
        $row = $result instanceof \mysqli_result ? $result->fetch_assoc() : null;
        return (int) ($row['total'] ?? 0);
    }
    public function systemRoleId(): ?int
    {
        $result = $this->db->execute('SELECT id FROM roles WHERE is_system=1 LIMIT 1');
        $row = $result instanceof \mysqli_result ? $result->fetch_assoc() : null;
        return isset($row['id']) ? (int) $row['id'] : null;
    }
}
