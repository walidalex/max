<?php

declare(strict_types=1);

namespace App\Core;

final class Environment
{
    public static function load(string $file): void
    {
        if (!is_file($file)) {
            return;
        }

        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = array_map('trim', explode('=', $line, 2));
            $externalValue = getenv($key);
            if ($key === '' || array_key_exists($key, $_ENV) || $externalValue !== false) {
                if ($key !== '' && $externalValue !== false && !array_key_exists($key, $_ENV)) {
                    $_ENV[$key] = $_SERVER[$key] = $externalValue;
                }
                continue;
            }
            $value = trim($value, "\"'");
            $_ENV[$key] = $_SERVER[$key] = $value;
            putenv($key . '=' . $value);
        }
    }
}
