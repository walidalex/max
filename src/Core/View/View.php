<?php

declare(strict_types=1);

namespace App\Core\View;

use App\Core\Config;
use App\Core\Http\Session;
use App\Core\Http\Csrf;

final class View
{
    /** @var (\Closure(): array<string, mixed>)|null */
    private ?\Closure $sharedDataProvider = null;

    public function __construct(private readonly string $path, private readonly Config $config, private readonly Session $session, private readonly Csrf $csrf) {}

    /** @param \Closure(): array<string, mixed> $provider */
    public function shareUsing(\Closure $provider): void { $this->sharedDataProvider = $provider; }
    /** @param array<string, mixed> $data */
    public function render(string $view, array $data = [], string $layout = 'layouts/app'): string
    {
        $sharedData = $this->sharedDataProvider !== null ? ($this->sharedDataProvider)() : [];
        $data = array_merge($sharedData, $data);
        $data['csrfToken'] ??= $this->csrf->token();
        $content = $this->renderFile($view, $data);
        return $this->renderFile($layout, array_merge($data, ['content' => $content, 'appName' => $this->config->get('app.name'), 'currentUser' => $this->session->get('auth_user'), 'flash' => $this->session->pullFlash('alert')]));
    }
    /** @param array<string, mixed> $data */
    public function component(string $component, array $data = []): string { return $this->renderFile('components/' . $component, $data); }
    /** @param array<string, mixed> $data */
    private function renderFile(string $view, array $data): string
    {
        $file = $this->path . '/' . str_replace('.', '/', $view) . '.php';
        if (!is_file($file)) { throw new \RuntimeException("View [{$view}] not found."); }
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string) ob_get_clean();
    }
}
