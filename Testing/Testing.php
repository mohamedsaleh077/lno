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

$t->select("table")->where($where)
    ->order(["col1", "col2"=> "asc", "{$t->subQuery()->select('suboo')->where(["sqlo", "=", "valuo"])}" => "desc", "col4"])
    ->withSQL($withs)
    ->select("sorry")
    ->rawSQL("Testing for RAW SQL")
    ->insert("table", ["id", "username", "passsowtrd"])
    ->values([14, "user", "pasopaso"])
    ->values([16, "usero", "pasopaso"])
    ->insert("halo", ["h", "w"])
    ->select("helo", ["h", "w"])->where(["h", "in", "{1, 2, 3, 4, 5}"])
    ->update("ta", ["col"=>"val", "colo"=>12])
    ->where(["id", "!=", 4])
    ->delete("t")
    ->where(["id", "<", 60])
    ->callDB()
;
//print_r($t->getParams());
//print_r($t->getQuery());
//print_r($t->getQueries());