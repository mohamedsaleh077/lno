<?php
declare(strict_types=1);
// I used https://github.com/Wagner-Souza/eloquent-orm/blob/main/orm/Database.php as a reference
namespace Mohamedsaleh077\Lno;
use Mohamedsaleh077\Lno\DatabaseInterface;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

class MySQL_Driver
implements DatabaseInterface
{
    private static $config;
    private static $connection;

    /**
     * @param string $config pass the config.ini file path, DOCUMENT_ROOT included.
     */
    public function __construct(string $config) {
        self::$config = parse_ini_file($_SERVER["DOCUMENT_ROOT"] . $config, true);
    }
    // check for connection
    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            self::connect();
        }

        return self::$connection;
    }

    // connect to database
    private static function connect(): void
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            self::$config['dbhost'],
            self::$config['port'] ?? 3306,
            self::$config['dbname'],
            self::$config['charset'] ?? 'utf8'
        );

        try {
            self::$connection = new PDO(
                $dsn,
                self::$config['username'],
                self::$config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            throw new RuntimeException("DB CONN: " . $e->getMessage());
        }
    }

    // execute a query
    private static function execute(string $sql, array $params = []): PDOStatement
    {
        $connection = self::getConnection();
        $statement = $connection->prepare($sql);
        $statement->execute($params);

        return $statement;
    }

    // public functions
    // getting all raws or one raw
    public static function Fetch(string $sql, array $params = [], bool $all = false): array
    {
        $stm = self::execute($sql, $params);
        $result = [
            "ok" => 0,
            "edited" => 0,
            "results" => []
        ];
        // if INSERT/UPDATE/DELETE
        if($stm->columnCount() === 0){
            $result["edited"] = $stm->rowCount();
            $result["ok"] = $result["edited"] > 0;
            if(str_starts_with($sql, "INSERT")){
                $result["lastID"] = self::lastInsertId();
            }
        }
        
        // if SELECT
        $r = [];
        if($all){
            $r = $stm->fetchAll(PDO::FETCH_ASSOC);
            $result["len"] = count($r);
        } else {
            $r = $stm->fetch(PDO::FETCH_ASSOC);
        }
        
        if(!empty($r)){
            $result["ok"] = 1;
            $result["results"] = $r;            
        }

        return $result;
    }

    // last insert id
    public static function lastInsertId(): string
    {
        return self::getConnection()->lastInsertId();
    }

    // start Transaction
    public static function beginTransaction(): bool
    {
        return self::getConnection()->beginTransaction();
    }

    /**
     * Transaction'ı onayla
     */
    public static function commit(): bool
    {
        return self::getConnection()->commit();
    }

    /**
     * Transaction'ı geri al
     */
    public static function rollback(): bool
    {
        return self::getConnection()->rollBack();
    }

    public static function disconnect(): void
    {
        self::$connection = null;
    }
}
