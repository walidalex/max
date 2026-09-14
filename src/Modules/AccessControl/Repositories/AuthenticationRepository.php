<?php
declare(strict_types=1);
namespace App\Modules\AccessControl\Repositories;
use App\Core\Database\Database;
final class AuthenticationRepository
{
    public function __construct(private readonly Database $database) {}
    /** @return array<string, mixed>|null */
    public function findByLogin(string $login): ?array
    {
        $result = $this->database->execute('SELECT id, username, name, email, password_hash, is_active FROM users WHERE username = ? OR email = ? LIMIT 1', [$login, $login]);
        $row = $result instanceof \mysqli_result ? $result->fetch_assoc() : null;
        return is_array($row) ? $row : null;
    }
    /** @return array<string, mixed>|null */
    public function findActiveById(int $id): ?array
    {
        $result = $this->database->execute('SELECT id, username, name, email, is_active FROM users WHERE id = ? AND is_active = 1 LIMIT 1', [$id]);
        $row = $result instanceof \mysqli_result ? $result->fetch_assoc() : null;
        return is_array($row) ? $row : null;
    }
    public function touchLastLogin(int $id): void { $this->database->execute('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$id]); }
}
