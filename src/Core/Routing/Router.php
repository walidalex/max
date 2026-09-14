<?php

declare(strict_types=1);

namespace App\Core\Routing;

use App\Core\Application;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Modules\AccessControl\Services\AuthenticationService;
use App\Modules\AccessControl\Services\AuthorizationService;

final class Router
{
    /** @var array<string, list<array{path: string, handler: callable|array{class-string, string}, middleware: list<string>}>> */
    private array $routes = [];

    public function __construct(private readonly Application $app) {}

    /** @param list<string> $middleware */
    public function get(string $path, callable|array $handler, array $middleware = []): void { $this->add('GET', $path, $handler, $middleware); }
    /** @param list<string> $middleware */
    public function post(string $path, callable|array $handler, array $middleware = []): void { $this->add('POST', $path, $handler, $middleware); }
    /** @param list<string> $middleware */
    public function add(string $method, string $path, callable|array $handler, array $middleware = []): void
    {
        $this->routes[$method][] = ['path' => $this->normalize($path), 'handler' => $handler, 'middleware' => $middleware];
    }

    public function dispatch(Request $request): Response
    {
        $route = $this->match($request->method(), $request->path());
        if ($route === null) {
            return $request->expectsJson() ? Response::json(['message' => 'Not found'], 404) : Response::html('<h1>404</h1>', 404);
        }
        $request->setRouteParams($route['params']);
        $middlewareResponse = $this->runMiddleware($route['middleware'], $request);
        if ($middlewareResponse instanceof Response) { return $middlewareResponse; }

        $handler = $route['handler'];
        if (is_array($handler) && is_string($handler[0])) { $handler = [$this->app->make($handler[0]), $handler[1]]; }
        $response = $handler($request);
        return $response instanceof Response ? $response : Response::html((string) $response);
    }

    private function normalize(string $path): string
    {
        $normalized = '/' . trim($path, '/');
        return $normalized === '/' ? '/' : rtrim($normalized, '/');
    }

    /** @return array{handler: callable|array{class-string, string}, middleware: list<string>, params: array<string, string>}|null */
    private function match(string $method, string $path): ?array
    {
        $path = $this->normalize($path);
        foreach ($this->routes[$method] ?? [] as $route) {
            [$pattern, $names] = $this->compile($route['path']);
            if (!preg_match($pattern, $path, $matches)) { continue; }
            array_shift($matches);
            return ['handler' => $route['handler'], 'middleware' => $route['middleware'], 'params' => array_combine($names, array_map('urldecode', $matches)) ?: []];
        }
        return null;
    }

    /** @return array{string, list<string>} */
    private function compile(string $path): array
    {
        $names = [];
        $cursor = 0;
        $pattern = '';
        preg_match_all('/\{([A-Za-z_][A-Za-z0-9_]*)\}/', $path, $matches, PREG_OFFSET_CAPTURE);
        foreach ($matches[0] as $index => [$placeholder, $offset]) {
            $pattern .= preg_quote(substr($path, $cursor, $offset - $cursor), '#') . '([^/]+)';
            $names[] = $matches[1][$index][0];
            $cursor = $offset + strlen($placeholder);
        }
        $pattern .= preg_quote(substr($path, $cursor), '#');
        return ['#^' . $pattern . '$#', $names];
    }

    /** @param list<string> $middleware */
    private function runMiddleware(array $middleware, Request $request): ?Response
    {
        foreach ($middleware as $item) {
            $authentication = $this->app->make(AuthenticationService::class);
            if ($item === 'guest' && $authentication->isAuthenticated()) { return Response::redirect('/'); }
            if ($item === 'auth' && !$authentication->validateSession()) {
                return $request->expectsJson() ? Response::json(['message' => 'Unauthenticated'], 401) : Response::redirect('/login');
            }
            if (str_starts_with($item, 'permission:')) {
                $permission = substr($item, strlen('permission:'));
                if (!$this->app->make(AuthorizationService::class)->can($permission)) {
                    return $request->expectsJson() ? Response::json(['message' => 'Forbidden'], 403) : Response::html('<h1>403</h1><p>ليس لديك صلاحية لتنفيذ هذا الإجراء.</p>', 403);
                }
            }
        }
        return null;
    }
}
