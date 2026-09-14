<?php

declare(strict_types=1);

use App\Core\Application;
use App\Core\Config;
use App\Core\Environment;

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

require $basePath . '/routes/web.php';
require $basePath . '/routes/api.php';

return $app;
