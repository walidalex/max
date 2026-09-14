<?php

declare(strict_types=1);

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        return ($value === false || $value === null) ? $default : $value;
    }
}

if (!function_exists('env_bool')) {
    function env_bool(string $key, bool $default = false): bool
    {
        $value = env($key);
        return $value === null ? $default : filter_var($value, FILTER_VALIDATE_BOOL);
    }
}

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('decimal_format')) {
    function decimal_format(string $value, int $scale = 2): string
    {
        if (!preg_match('/^(-?)(\d+)(?:\.(\d+))?$/', trim($value), $matches)) return $value;
        $whole = preg_replace('/\B(?=(\d{3})+(?!\d))/', ',', ltrim($matches[2], '0') ?: '0');
        return $matches[1] . $whole . ($scale > 0 ? '.' . str_pad(substr($matches[3] ?? '', 0, $scale), $scale, '0') : '');
    }
}
