<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Auth\Auth;
use App\Core\Database\Database;
use App\Core\Exceptions\ExceptionHandler;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\Session;
use App\Core\Http\Csrf;
use App\Core\Routing\Router;
use App\Core\Support\Logger;
use App\Core\View\View;
use Throwable;

final class Application
{
    private Router $router;
    private Session $session;
    private Logger $logger;
    private Database $database;
    private View $view;
    private Auth $auth;
    private Csrf $csrf;

    public function __construct(private readonly string $basePath, private readonly Config $config) {}

    public function boot(): void
    {
        date_default_timezone_set((string) $this->config->get('app.timezone', 'UTC'));
        ini_set('display_errors', '0');
        set_error_handler(static function (int $severity, string $message, string $file, int $line): never {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });
        $this->logger = new Logger($this->basePath . '/storage/logs/app.log', (string) env('LOG_LEVEL', 'error'));
        $this->session = new Session((array) $this->config->get('auth', []));
        $this->session->start();
        $this->csrf = new Csrf($this->session);
        $this->database = new Database((array) $this->config->get('database', []));
        $this->view = new View($this->basePath . '/resources/views', $this->config, $this->session, $this->csrf);
        $this->auth = new Auth($this->session);
        $this->router = new Router($this);
    }

    public function handle(Request $request): Response
    {
        try {
            return $this->router->dispatch($request);
        } catch (Throwable $exception) {
            return (new ExceptionHandler($this->logger, (bool) $this->config->get('app.debug', false)))->render($exception, $request);
        }
    }

    public function make(string $class): object
    {
        return match ($class) {
            Config::class => $this->config,
            Database::class => $this->database,
            Session::class => $this->session,
            Csrf::class => $this->csrf,
            View::class => $this->view,
            Auth::class => $this->auth,
            Logger::class => $this->logger,
            default => $this->build($class),
        };
    }

    private function build(string $class): object
    {
        $reflection = new \ReflectionClass($class);
        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            return new $class();
        }
        $dependencies = array_map(function (\ReflectionParameter $parameter): object {
            $type = $parameter->getType();
            if (!$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
                throw new \RuntimeException("Cannot resolve {$parameter->getName()}.");
            }
            return $this->make($type->getName());
        }, $constructor->getParameters());
        return $reflection->newInstanceArgs($dependencies);
    }

    public function router(): Router { return $this->router; }
    public function config(): Config { return $this->config; }
}
