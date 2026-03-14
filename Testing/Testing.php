<?php
require_once '../vendor/autoload.php';
require_once "./Mockdb.php";
require_once "./PG_Driver.php";

use Mohamedsaleh077\Lno\PostgreSQL;
use Mohamedsaleh077\Lno\QueryBuilder;

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

//$t = new Testing(new PG_Driver());
//$t->update("table", ["t"=>"value"])->where(["i", "=", 5])
//    ->callDB();

$db = new PostgreSQL();
$db::getConnection();