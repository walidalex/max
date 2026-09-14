<?php

declare(strict_types=1);

namespace App\Core\Http;

final class Request
{
    /** @var array<string, string> */
    private array $routeParams = [];
    /** @param array<string, mixed> $query @param array<string, mixed> $body @param array<string, mixed> $server @param array<string, mixed> $files */
    public function __construct(private readonly array $query = [], private readonly array $body = [], private readonly array $server = [], private readonly array $files = []) {}

    public static function capture(): self
    {
        $body = $_POST;
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $decoded = json_decode(file_get_contents('php://input') ?: '{}', true);
            $body = is_array($decoded) ? $decoded : [];
        }
        return new self($_GET, $body, $_SERVER, $_FILES);
    }

    public function method(): string { return strtoupper((string) ($this->server['REQUEST_METHOD'] ?? 'GET')); }
    public function path(): string
    {
        $path = '/' . trim((string) parse_url($this->server['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
        return $path === '/' ? '/' : $path;
    }
    public function input(string $key, mixed $default = null): mixed { return $this->body[$key] ?? $this->query[$key] ?? $default; }
    /** @return array<string, mixed> */
    public function all(): array { return array_merge($this->query, $this->body); }
    public function expectsJson(): bool { return str_starts_with($this->path(), '/api/') || str_contains((string) ($this->server['HTTP_ACCEPT'] ?? ''), 'application/json'); }
    /** @param array<string, string> $params */
    public function setRouteParams(array $params): void { $this->routeParams = $params; }
    public function route(string $key, mixed $default = null): mixed { return $this->routeParams[$key] ?? $default; }
    public function file(string $key): ?UploadedFile
    {
        $file=$this->files[$key]??null;
        return is_array($file)&&!is_array($file['name']??null)?UploadedFile::fromArray($file):null;
    }
}
