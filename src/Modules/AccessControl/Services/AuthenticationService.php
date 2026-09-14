<?php
declare(strict_types=1);
namespace App\Modules\AccessControl\Services;
use App\Core\Auth\Auth;
use App\Core\Config;
use App\Core\Http\Session;
use App\Modules\AccessControl\DTOs\LoginData;
use App\Modules\AccessControl\Repositories\AuthenticationRepository;
final class AuthenticationService
{
    public function __construct(private readonly AuthenticationRepository $users,private readonly Auth $auth,private readonly Session $session,private readonly Config $config) {}
    public function attempt(LoginData $data): bool
    {
        $user=$this->users->findByLogin($data->login);
        if ($user===null || !(bool)$user['is_active'] || !password_verify($data->password,(string)$user['password_hash'])) { return false; }
        unset($user['password_hash']); $this->users->touchLastLogin((int)$user['id']); $this->auth->login($user); $this->session->put('_last_activity',time()); return true;
    }
    public function validateSession(): bool
    {
        $id=$this->auth->id(); if ($id===null) { return false; }
        $last=(int)$this->session->get('_last_activity',0); $timeout=(int)$this->config->get('auth.idle_timeout',7200);
        if ($last>0 && time()-$last>$timeout) { $this->auth->logout(); return false; }
        $user=$this->users->findActiveById($id); if ($user===null) { $this->auth->logout(); return false; }
        $this->auth->refresh($user); $this->session->put('_last_activity',time()); return true;
    }
    public function isAuthenticated(): bool { return $this->auth->check(); }
    public function logout(): void { $this->auth->logout(); }
}
