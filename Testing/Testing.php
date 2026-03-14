<?php
require_once '../vendor/autoload.php';
require_once "./Mockdb.php";
use Mohamedsaleh077\Lno\QueryBuilder;
use Mohamedsaleh077\Lno\MySQL_Driver;

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

$t = new Testing(new \Mohamedsaleh077\Testing\Mockdb());
$t->update("table", ["t"=>"value"])->where(["i", "=", 5])
    ->callDB();
