<?php

declare(strict_types=1);

namespace App\Core\Auth;

use App\Core\Http\Session;

final class Auth
{
    public function __construct(private readonly Session $session) {}
    public function check(): bool { return $this->session->get('auth_user') !== null; }
    /** @return array<string, mixed>|null */
    public function user(): ?array { $user = $this->session->get('auth_user'); return is_array($user) ? $user : null; }
    /** @param array<string, mixed> $user */
    public function login(array $user): void { $this->session->regenerate(); $this->session->put('auth_user', $user); }
    public function logout(): void { $this->session->invalidate(); }
    public function id(): ?int { $user = $this->user(); return isset($user['id']) ? (int) $user['id'] : null; }
    /** @param array<string, mixed> $user */
    public function refresh(array $user): void { $this->session->put('auth_user', $user); }
}
