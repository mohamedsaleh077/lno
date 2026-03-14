<?php

namespace Mohamedsaleh077\Testing;
use Mohamedsaleh077\Lno\DatabaseInterface;
class Mockdb
implements DatabaseInterface
{

    public static function Fetch(string $sql, array $params = [], bool $all = false): array|bool
    {
        $r = [
            "sql" => $sql,
            "params" => $params,
            "all" => $all,
        ];
        print_r($r);
        return true;
    }

    public static function beginTransaction(): bool
    {
        echo "begin transaction...\n";
        return true;
    }

    public static function commit(): bool
    {
        echo "commit transaction...\n";
        return true;
    }

    public static function rollback(): bool
    {
        echo "rollback transaction...\n";
        return true;
    }

    public static function DBType(): string
    {
        return "mysql";
    }
}