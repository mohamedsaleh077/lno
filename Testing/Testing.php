<?php
require_once '../vendor/autoload.php';
require_once './Mockdb.php';

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

$t = new Testing(new Mockdb());

$where = [
    [
        ["id", "=", 1],
        "AND",
        ["user", "=", "{$t->subQuery()->select('test')->where(["id", "=", 12])}"]
    ],
    "OR",
    ["id", "=", "14"]
];

$sub = $t->subQuery()->select('test')->where(["id", "=", 12]);

$withs = [
    "cta1" => $sub,
    "cta2" => $sub
];

$t->select("table")->where(["table.col", ">", 34])
    ->callDB()
;

print_r($t->getParams());
print_r($t->getQuery());
print_r($t->getQueries());