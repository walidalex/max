<?php

declare(strict_types=1);

$path = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
$publicPath = realpath(__DIR__);
$requestedFile = realpath(__DIR__ . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR));

if (
    $path !== '/'
    && $publicPath !== false
    && $requestedFile !== false
    && str_starts_with($requestedFile, $publicPath . DIRECTORY_SEPARATOR)
    && is_file($requestedFile)
) {
    $contentType = match (strtolower(pathinfo($requestedFile, PATHINFO_EXTENSION))) {
        'css' => 'text/css; charset=UTF-8',
        'js' => 'application/javascript; charset=UTF-8',
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        default => 'application/octet-stream',
    };
    header('Content-Type: ' . $contentType);
    header('Content-Length: ' . filesize($requestedFile));
    readfile($requestedFile);
    exit;
}

require __DIR__ . '/index.php';
