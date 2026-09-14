<?php

declare(strict_types=1);

namespace App\Core\Support;

use Throwable;

final class Logger
{
    private const PRIORITIES = ['debug' => 0, 'info' => 1, 'warning' => 2, 'error' => 3];
    public function __construct(private readonly string $file, private readonly string $minimumLevel = 'error') {}
    /** @param array<string, mixed> $context */
    public function error(string $message, array $context = []): void { $this->write('error', $message, $context); }
    /** @param array<string, mixed> $context */
    public function info(string $message, array $context = []): void { $this->write('info', $message, $context); }
    /** @param array<string, mixed> $context */
    private function write(string $level, string $message, array $context): void
    {
        if ((self::PRIORITIES[$level] ?? 3) < (self::PRIORITIES[$this->minimumLevel] ?? 3)) { return; }
        $context = array_map(static fn (mixed $value): mixed => $value instanceof Throwable ? ['class' => $value::class, 'message' => $value->getMessage(), 'trace' => $value->getTraceAsString()] : $value, $context);
        $line = sprintf("[%s] %s: %s %s%s", date(DATE_ATOM), strtoupper($level), $message, json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PHP_EOL);
        @file_put_contents($this->file, $line, FILE_APPEND | LOCK_EX);
    }
}
