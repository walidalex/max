<?php

declare(strict_types=1);

namespace App\Core\Http;

final class Session
{
    /** @param array<string, mixed> $config */
    public function __construct(private readonly array $config) {}

    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) { return; }
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_name((string) ($this->config['session_name'] ?? 'app_session'));
        session_set_cookie_params([
            'httponly' => true,
            'secure' => (bool) ($this->config['secure'] ?? false),
            'samesite' => (string) ($this->config['same_site'] ?? 'Lax'),
            'path' => '/',
        ]);
        session_start();
        if (!isset($_SESSION['_started_at'])) {
            session_regenerate_id(true);
            $_SESSION['_started_at'] = time();
        }
    }

    public function get(string $key, mixed $default = null): mixed { return $_SESSION[$key] ?? $default; }
    public function put(string $key, mixed $value): void { $_SESSION[$key] = $value; }
    public function forget(string $key): void { unset($_SESSION[$key]); }
    public function flash(string $key, mixed $value): void { $_SESSION['_flash'][$key] = $value; }
    public function pullFlash(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }
    public function regenerate(): void { if (!headers_sent()) { session_regenerate_id(true); } }
    public function invalidate(): void { $_SESSION = []; if (!headers_sent()) { session_regenerate_id(true); } }
}
