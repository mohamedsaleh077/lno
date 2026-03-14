<?php

namespace Mohamedsaleh077\Lno;

interface DatabaseInterface {
    public static function DBType(): string;
    public static function Fetch(string $sql, array $params = [], bool $all = false): array | bool;
    public static function beginTransaction(): bool;
    public static function commit(): bool;
    public static function rollback(): bool;
}
