<?php

declare(strict_types=1);

use App\Core\Application;
use App\Core\Config;
use App\Core\Environment;
use App\Core\Http\Session;
use App\Core\View\View;
use App\Modules\AccessControl\Services\AuthorizationService;
use App\Modules\CompanyProfile\Repositories\CompanyProfileRepository;

$basePath = dirname(__DIR__);

require $basePath . '/src/Shared/helpers.php';

$autoload = $basePath . '/vendor/autoload.php';
if (is_file($autoload)) {
    require $autoload;
} else {
    spl_autoload_register(static function (string $class) use ($basePath): void {
        $prefix = 'App\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }
        $file = $basePath . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) {
            require $file;
        }
    });
}

Environment::load($basePath . '/.env');
$config = Config::load($basePath . '/config');

$app = new Application($basePath, $config);
$app->boot();

/** @var View $view */
$view = $app->make(View::class);
$view->shareUsing(static function () use ($app): array {
    /** @var Session $session */
    $session = $app->make(Session::class);
    if (!is_array($session->get('auth_user'))) {
        return [];
    }

    /** @var CompanyProfileRepository $profiles */
    $profiles = $app->make(CompanyProfileRepository::class);
    $company = $profiles->get();
    /** @var AuthorizationService $authorization */
    $authorization = $app->make(AuthorizationService::class);
    return [
        'companyBrand' => $company,
        'canView' => static fn (string $permission): bool => $authorization->can($permission),
    ];
});

require $basePath . '/routes/web.php';
require $basePath . '/routes/api.php';

return $app;
