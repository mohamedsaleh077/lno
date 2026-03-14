<?php

namespace Mohamedsaleh077\Lno;
use Mohamedsaleh077\Lno\DatabaseInterface;
use Mohamedsaleh077\Lno\QueryBuilderHelper;

class OP
{
    public bool $subOb = false;
    protected array $queries = [];
    protected array $query = [];
    protected array $params = [];
    protected int $paramsCount = 0;

    // Import Helper Trait
    use QueryBuilderHelper;

    public function __construct(private DatabaseInterface $db){
    }

    public function __toString(): string
    {
        return "{ " . $this->buildQuery() . " }";
    }

    protected function setParams($value): string
    {
        if($this->subOb === true){
            return $this->parent->setParams($value);
        }
        $result = ":p" . $this->paramsCount;
        $this->params[$result] = $value;
        $this->paramsCount++;
        return $result;
    }

    public function buildQuery() : string
    {
        $result = [];
        $query = [];
        if(isset($this->query["rawsql"])){
            foreach($this->query["rawsql"] as $key => $value){
                $query[$key] = $value;
            }
        }

        if(isset($this->query["delete"])){
            if(!isset($this->query["where"])){
                $this->errorHandler(1008, "");
            }
            if(isset($this->query["delete"])) $result[] = $this->query["delete"];
            if(isset($this->query["where"])) $result[] = $this->query["where"];
        }else if(isset($this->query["update"])){
            if(!isset($this->query["where"])){
                $this->errorHandler(1007, "");
            }
            if(isset($this->query["update"])) $result[] = $this->query["update"];
            if(isset($this->query["where"])) $result[] = $this->query["where"];
        } else if(isset($this->query["insert"])){

            if(!isset($this->query["select"]) && !isset($this->query["values"])){
                $this->errorHandler(1005, "");
            }

            if(isset($this->query["select"]) && !isset($this->query["where"])){
                $this->errorHandler(1006, "");
            }

            if(isset($this->query["insert"])) $result[] = $this->query["insert"];
            if(isset($this->query["values"])) array_push($result, " VALUES ", implode(",", $this->query["values"]));
            if(isset($this->query["select"])) $result[] = $this->query["select"];
            if(isset($this->query["where"])) $result[] = $this->query["where"];
            if($this->db->DBType() === "pgsql") $result[] = ' RETURNING "id"';
        }else{
            if(isset($this->query["with"])){
                $result[] = $this->query["with"];
            }
            if(isset($this->query["select"])){
                $result[] = $this->query["select"];
                if(isset($this->query["joins"]))    array_push($result, ...$this->query["joins"]);
                if(isset($this->query["where"]))    $result[] = $this->query["where"];
                if(isset($this->query["groupby"]))  $result[] = $this->query["groupby"];
                if(isset($this->query["having"]))   $result[] = $this->query["having"];
                if(isset($this->query["orderby"]))  $result[] = $this->query["orderby"];
                if(isset($this->query["limit"]))    $result[] = $this->query["limit"];
            }else{
                $this->errorHandler(1012, "");
            }
        }

        $index = 0;
        foreach($result as $part){
            while(isset($query[$index])){
                $index++;
            }
            $query[$index] = $part;
        }

        ksort($query);
        $query = array_filter($query);

        return implode(" ", $query);
    }

    public function saveQuery(): self
    {
        $this->queries[$this->BuildQuery()] = $this->params;
        $this->query = [];
        $this->params = [];

        return $this;
    }

    // written by AI errr
    protected function paramSetter(string $sql, array $params): array
    {
        $finalParams = [];

        preg_match_all('/:p\d+/', $sql, $matches);
        $placeholdersInOrder = $matches[0];

        foreach ($placeholdersInOrder as $placeholder) {
            if (isset($params[$placeholder])) {
                $finalParams[$placeholder] = $params[$placeholder];
            }
        }

        return [
            'sql' => $sql,
            'params' => $finalParams
        ];
    }

    /**
     * Execute all Queries.
     * * @param bool $all default false for defining fetchAll
     * * @return array return the results.
     */
    public function callDB(bool $all = false) : array
    {
        if(!empty($this->query)){
            $this->saveQuery();
        }
        if(empty($this->queries)){
            $this->errorHandler(1010, "no Queries to Execute.");
        }
        $result = [];
        try {
            $this->db::beginTransaction();
            foreach ($this->queries as $key => $value) {
                $fixed = $this->paramSetter($key, $value);
                $result[] = $this->db::Fetch($fixed["sql"], $fixed["params"], $all);
            }
            $this->db::commit();
            $this->queries = [];
        }catch(\Throwable $e){
            $this->db::rollback();
            $this->errorHandler(1009, $e->getMessage());
        }

        return $result;
    }

}