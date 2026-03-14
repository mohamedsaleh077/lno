<?php
require_once '../vendor/autoload.php';

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

$t = new Testing(new MySQL_Driver());
echo $_SERVER['DOCUMENT_ROOT'];
