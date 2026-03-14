<?php
require_once '../vendor/autoload.php';
require_once "./Mockdb.php";
//require_once "./PG_Driver.php";

use Mohamedsaleh077\Lno\PostgreSQL;
use Mohamedsaleh077\Lno\QueryBuilder;
use Mohamedsaleh077\Testing\Mockdb;

Class Testing extends QueryBuilder{
    public function getParams(){
        return $this->params;
    }
    public function getQuery(){
        return $this->query;
    }

    public function getQueries(){
        return $this->queries;
    }
}

// 1. إعداد التعريفات الأساسية
$mysqlDriver = new Mockdb();
$t = new QueryBuilder($mysqlDriver);

$t->select("table")
    ->where([
        ["table.col", ">", 15],
        "and", [
            ["num", "not", "null"],
            "or",
            ["table.col", "<", "value"]
        ]
    ])
    ->select(["users", "u"])
    ->join(["posts" => "p", "p.user_id", "u.id"])
    ->join(["comments" => "c", "c.user_id", "u.id"], "right")
    ->select(["users"])
    ->groupby("col")
    ->select(["users"])
    ->having("x > 5")
    ->select(["users"])
    ->order(["col1" => "asc", "col2", "col3" => "desc"]);
$sub = $t->subQuery()
    ->select('test')
    ->where(["id", "=", 12]);

$withs = [
    "cta1" => $sub,
    "cta2" => $sub
];

$t->select("table")
    ->where(["id", "=", 12])
    ->withSQL($withs)
    ->select(["users"])
    ->rawSQL("this is a raw sql")
    ->limit(1, 14)
    ->insert("table_name", ["username", "fullname"])
        ->values(["u1", "f1"])
        ->values(["u2", "f2"])
        ->values(["u3", "f3"])
    ->update("table", ["t"=>"value"])->where(["i", "=", 5])
    ->delete("users")
    ->where(["id", "=", 2])
    ->callDB()
    ;