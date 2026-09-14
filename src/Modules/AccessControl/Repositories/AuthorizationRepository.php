<?php
declare(strict_types=1);
namespace App\Modules\AccessControl\Repositories;
use App\Core\Database\Database;
final class AuthorizationRepository
{
    public function __construct(private readonly Database $database) {}
    public function userHasRole(int $userId, string $roleCode): bool
    {
        $result = $this->database->execute('SELECT 1 FROM user_roles ur INNER JOIN roles r ON r.id = ur.role_id WHERE ur.user_id = ? AND r.code = ? AND r.is_active = 1 LIMIT 1', [$userId, $roleCode]);
        return $result instanceof \mysqli_result && $result->num_rows === 1;
    }
    public function userHasPermission(int $userId, string $permissionCode): bool
    {
        $sql = 'SELECT 1 FROM user_roles ur INNER JOIN roles r ON r.id = ur.role_id INNER JOIN role_permissions rp ON rp.role_id = r.id INNER JOIN permissions p ON p.id = rp.permission_id WHERE ur.user_id = ? AND r.is_active = 1 AND p.code = ? LIMIT 1';
        $result = $this->database->execute($sql, [$userId, $permissionCode]);
        return $result instanceof \mysqli_result && $result->num_rows === 1;
    }
}
