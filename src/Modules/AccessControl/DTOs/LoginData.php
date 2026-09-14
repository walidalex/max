<?php
declare(strict_types=1);
namespace App\Modules\AccessControl\DTOs;
final readonly class LoginData { public function __construct(public string $login, public string $password) {} }
