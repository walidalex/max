<?php

declare(strict_types=1);

namespace App\Core\Database;

use mysqli;
use mysqli_sql_exception;

final class Database
{
    private ?mysqli $connection = null;

    /** @param array<string, mixed> $config */
    public function __construct(private readonly array $config) {}

    public function connection(): mysqli
    {
        if ($this->connection instanceof mysqli) {
            return $this->connection;
        }
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $this->connection = new mysqli(
            (string) $this->config['host'],
            (string) $this->config['username'],
            (string) $this->config['password'],
            (string) $this->config['database'],
            (int) $this->config['port'],
        );
        $this->connection->set_charset((string) $this->config['charset']);
        return $this->connection;
    }

    /** @param list<mixed> $params */
    public function execute(string $sql, array $params = []): \mysqli_result|bool
    {
        $statement = $this->connection()->prepare($sql);
        try {
            if ($params !== []) {
                $statement->bind_param($this->parameterTypes($params), ...$params);
            }
            $statement->execute();
            return $statement->get_result();
        } finally {
            $statement->close();
        }
    }

    /** @param list<mixed> $params */
    private function parameterTypes(array $params): string
    {
        return implode('', array_map(static fn (mixed $value): string => match (true) {
            is_int($value), is_bool($value) => 'i',
            is_float($value) => 'd',
            default => 's',
        }, $params));
    }

    public function transaction(callable $callback): mixed
    {
        $connection = $this->connection();
        $connection->begin_transaction();
        try {
            $result = $callback($this);
            $connection->commit();
            return $result;
        } catch (\Throwable $exception) {
            $connection->rollback();
            throw $exception;
        }
    }
}
