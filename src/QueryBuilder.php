<?php

namespace Mohamedsaleh077\Lno;
use Mohamedsaleh077\Lno\QueryBuilderHelper;

use Exception;
//use Mohamedsaleh077\Lno\QueryBuilderHelper;
//use Mohamedsaleh077\Lno\DatabaseInterface;

class QueryBuilder
{
    private array $queries;
    private array $query;

    const SQL_RESERVED = [
        'NULL',
        'TRUE',
        'FALSE',
        'UNKNOWN',
        'DEFAULT'
    ];

    const SQL_LOGIC_OPERATORS = [
        "AND",
        "OR",
        "XOR"
    ];

    /**
     * @param DatabaseInterface $db you need to pass the class that will handle the database,
     * this class should have these static methods:
     * Fetch, beginTransaction, commit, rollback.
     */
    public function __construct(private DatabaseInterface $db)
    {
        $this->queries = [];
        $this->query = [];
    }

    // Import Helper Trait
    use QueryBuilderHelper;

    /**
     * Build the SELECT part of the query.
     * * @param string | array $tableName The main table name or tablename AS alias ["table", "alias"].
     * @param array<int|string, string> $columns An associative or indexed array of columns.
     * Format examples:
     * - ["column"]             => `column`
     * - ["table.column"]       => `table`.`column`
     * - ["table.*"]            => `table`.*
     * - ["col" => "alias"]     => `col` AS `alias`
     * - ["{RAW_SQL}"]          => RAW_SQL
     * - ["{RAW_SQL}" => "al"]  => RAW_SQL AS `al`
     * * @warning 5000 Triggers if using {RAW_CODE} - User is responsible for SQL safety.
     * * @return self Returns $this for chaining.
     * @throws Exception If illegal aliasing (like * AS alias) is detected (Error 1000-1002).
     */
    public function select(string|array $tableName, array $columns = ["*"]): self
    {
        if(!empty($this->query) && !isset($this->query["insert"]) || isset($this->query["select"])){
            $this->saveQuery();
        }
        $result = [];
        foreach ($columns as $key => $value) {
            $firstCharKey = is_int($key) ? $key : substr($key, 0, 1);
            $firstCharValue = substr($value, 0, 1);

            if (is_int($key)) {
                if ($firstCharValue === "{") {
                    $this->warningHandler(5000, $value);
                    $tmp = substr($value, 1, -1);
                    $result[] = $tmp;
                    continue;
                }
                $columnFiltered = $this->dotSplitter($value);
                $result[] = $columnFiltered;
            }

            if (!is_int($key)) {
                if ($firstCharKey === "{") {
                    $this->warningHandler(5000, $key);
                    $tmp = substr($key, 1, -1);
                    $result[] = $tmp . " AS `" . trim($value) . "`";
                    continue;
                }
                if ($key[0] == "*") {
                    $this->errorHandler(1000, $key . "=>" . trim($value));
                }
                $columnFiltered = $this->dotSplitter($key);
                if (!$columnFiltered) {
                    $this->errorHandler(1001, $key);
                }
                if ($columnFiltered[strlen($columnFiltered) - 1] === "*") {
                    $this->errorHandler(1002, $columnFiltered);
                }
                $result[] = $columnFiltered . " AS `" . trim($value) . "`";
            }
        }
        $columnsString = implode(",", $result);

        if (is_array($tableName)) {
            $tableName = implode("` AS `", $tableName);
        }
        $this->query["select"] = "SELECT " . $columnsString . " FROM `" . $tableName . "`";
        return $this;
    }

    /**
     * Build the WHERE part of the query.
     * @param array<int|string, string> $columns An associative or indexed array of columns.
     * Format examples:
     * - [ "table.col1", "=", "param1" ] => `table`.`col1` = :param1
     * - [ ["t.a", ">", "p1"], "and", [ "t.b", "!=", "p2" ] ] => ((`t`.`a` < :p1) AND (`t`.`b` != :p2))
     * - [ ["{RAW_SQL}", "=", "null"], "and", [ [ "t.b", "!=", "p2" ], 'or', ["t.b", "like", "te"] ] ]
     * ((RAW_SQL = NULL) AND ((`t`.`b` != :p2) OR (`t`.`b` LIKE :te)))
     * * @warning 5000 Triggers if using {RAW_CODE}, you will be responsible for what you write, I will not touch what
     * between {heh}.
     * * @warning 5001 Triggers if using Reserved word in SQL, it will be considered as an SQL word not a param.
     * * @return self Returns $this for chaining.
     * @throws Exception If illegal aliasing (like * AS alias) is detected (Error 1000-1002).
     */
    public function where(array $columns): self
    {
        $this->query["where"] = "WHERE " . implode(" ", $this->whereRecursion($columns));
        return $this;
    }

    private function whereRecursion(array $columns): array
    {
        $result = [];
        if (count($columns) < 4 && is_string($columns[0])) {
            $result[] = (str_starts_with($columns[0], "{")) ? "(" . substr($columns[0], 1, -1) . ")" : $this->dotSplitter($columns[0]);
            $result[] = strtoupper($columns[1]);
            $result[] = (str_starts_with($columns[2], "{")) ? "(" . substr($columns[2], 1, -1) . ")" : ":" . $columns[2];
            return $result;
        }
        foreach ($columns as $value) {
            if (is_array($value)) {
                $result[] = "(";
                if (is_array($value[0])) {
                    $result = array_merge_recursive($result, $this->whereRecursion($value));
                    $result[] = ")";
                } else {
                    if (str_starts_with($value[0], "{")) {
                        $this->warningHandler(5000, $value[2]);
                        $result[] = "(" . substr($value[0], 1, -1) . ")";
                    } else {
                        $result[] = $this->dotSplitter($value[0]);
                    }
                    $result[] = strtoupper($value[1]);
                    if (str_starts_with($value[2], "{")) {
                        $this->warningHandler(5000, $value[2]);
                        $result[] = "(" . substr($value[2], 1, -1) . ")";
                    } else {
                        $tmp = ":" . trim($value[2]);
                        if (in_array(strtoupper($value[2]), self::SQL_RESERVED)) {
                            $this->warningHandler(5001, $value[2]);
                            $tmp = strtoupper($value[2]);
                        }
                        $result[] = $tmp;
                    }
                    $result[] = ")";
                }
            }
            if (is_string($value)) {
                $result[] = strtoupper($value);
            }
        }
        return $result;
    }

    /**
     * Build the JOIN part of the query.
     * * @param array $params are the table with its alias and columns.
     * @param string $method (OPTIONAL) for spcificing (LEFT, RIGHT, FULL)
     * Format examples:
     * - ["table", "members.sold", "dddd"] => `table` ON `members`.`sold` = `dddd`
     * - ["table" => "t", "members.sold", "t.dddd"] => `table` AS `t` ON `members`.`sold` = `t`.`dddd`
     * * @return self Returns $this for chaining.
     */
    public function join(array $params, string $method = ""): self
    {
        $result = [];
        foreach ($params as $key => $value) {
            if (is_int($key)) {
                $result[] = $this->dotSplitter($value);
            }
            if (is_string($key)) {
                $result[] = $this->dotSplitter($key) . " AS " . $this->dotSplitter($value);
            }
        }
        $this->query["joins"][] = strtoupper($method) . " JOIN " . $result[0] . " ON " . $result[1] . " = " . $result[2];
        return $this;
    }

    /**
     * Build the GROUP BY part of the query.
     * * @param string $column for the specific column.
     * * @return self Returns $this for chaining.
     */
    public function groupBy(string $column): self
    {
        $this->query["groupby"] = "GROUP BY " . $this->dotSplitter($column) ;
        return $this;
    }

    /**
     * Build the HAVING part of the query.
     * * @param string $condition for RAW SQL conditions.
     * * @return self Returns $this for chaining.
     */
    public function having(string $condition): self
    {
        $this->warningHandler(5000, $condition);
        $this->query["having"] = "HAVING " . $condition;
        return $this;
    }

    /**
     * Build the ORDER BY part of the query.
     * * @param array $columns the columns that will be used.
     * Format examples:
     * - ["col1", "col2"] => `col1`, `col2`
     * - ["col1" => "desc", "col2" => "asc"] => `col1` DESC, `col2` ASC
     * * @return self Returns $this for chaining.
     */
    public function order(array $columns): self
    {
        $result = [];
        foreach ($columns as $key => $value) {
            if(is_int($key)) {
                if(str_starts_with($value, "{")) {
                    $this->warningHandler(5000, $value);
                    $result[] = "(" . substr($value, 1, -1) . ")";
                }else{
                    $result[] = "`" . $value . "`";
                }
            }else{
                if(str_starts_with($key, "{")) {
                    $this->warningHandler(5000, $key);
                    $result[] = "(" . substr($key, 1, -1) . ") " . strtoupper($value);
                }else{
                    $result[] = "`" . $key . "` " . strtoupper($value);
                }
            }
        }
        $this->query["orderby"] = "ORDER BY " . implode(", ", $result);
        return $this;
    }

    /**
     * Build the LIMIT part of the query.
     * * @param int $limit how many records you want to see.
     * @param int $offset where should is start?
     * * @return self Returns $this for chaining.
     */
    public function limit(int $limit, int $offset = 0): self
    {
        $this->query["limit"] = "LIMIT $offset, $limit";
        return $this;
    }

    /**
     * Build the UNION part of the query.
     * * @param string $all UNION ALL or UNION.
     * this will add a UNION, start a new query after that.
     * * @return self Returns $this for chaining.
     */
    public function union(string $all = ""): self
    {
        $this->union = true;
        $this->saveQuery();
        $this->queries["union"] = "UNION " . strtoupper($all);
        return $this;
    }

    /**
     * Build any SQL part.
     * * @param string $sql for RAW SQL.
     * if you want to add something doesn't exsits, you can write a RAW SQL on your risk.
     * * @return self Returns $this for chaining.
     */
    public function rawSQL(string $sql): self
    {
        $this->warningHandler(5000, $sql);
        $this->query["rawsql"][count($this->query)] = $sql;
        return $this;
    }

    /**
     * Build INSERT part.
     * * @param string $tableName for table name.
     * * @param array $columns for columns
     * * @return self for chaining
     */
    public function insert(string $tableName, array $columns ): self
    {
        if(!empty($this->query)){
            $this->saveQuery();
        }
        $fields = "`" . implode("`, `", $columns) . "`";
        $this->query["insert"] = " INSERT INTO `" . $tableName . "` (" . $fields . ")";
        return $this;
    }

    /**
     * Build VALUES for INSERT part.
     * * @param array $values for placeholders
     * ex:
     * ["a", "b", "c"] => (:a, :b, :c),
     * * @return self for chaining
     */
    public function values(array $values): self
    {
        if(isset($this->query["select"])) $this->errorHandler(1004, "");
        $this->query["values"][] = "( :" . implode(", :", $values) . " )";
        return $this;
    }

    /**
     * Build UPDATE part.
     * * @param string $table for table name.
     * * @param array $columns for columns and placeholders.
     * **NOTES**
     * - if the item in the array is key-value, SET key = :value
     * - if the item have int key, SET value = :value
     * **example**
     * ["col" => "alias", "col2"] => SET col = :alias, col2 = :col2
     * * @return self for chaining
     */
    public function update(string $table, array $columns) : self
    {
        if(!empty($this->query)){
            $this->saveQuery();
        }

        $result = [];
        foreach($columns as $key => $value){
            if(is_int($key)){
                $result[] = "`" . $value . "` = :" . $value;
            } else {
                $result[] = "`" . $key ."` = :" . $value;
            }
        }
        $this->query["update"] =  "UPDATE `" . $table . "` SET" . implode(", ", $result);
        return $this;
    }

    /**
     * Build DELETE part.
     * * @param string $table for table name.
     * * @return self for chaining
     */
    public function delete(string $table) :self
    {
        if(!empty($this->query)){
            $this->saveQuery();
        }
        $this->query["delete"] = " DELETE FROM `" . $table . "` ";
        return $this;
    }


    /**
     * add current built query from query var to queries var.
     * * @return void.
     */
    public function saveQuery() : void
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
        }else{
            if(isset($this->query["select"])){
                $result[] = $this->query["select"];
                if(isset($this->query["joins"]))    array_push($result, ...$this->query["joins"]);
                if(isset($this->query["where"]))    $result[] = $this->query["where"];
                if(isset($this->query["groupby"]))  $result[] = $this->query["groupby"];
                if(isset($this->query["having"]))   $result[] = $this->query["having"];
                if(isset($this->query["orderby"]))  $result[] = $this->query["orderby"];
                if(isset($this->query["limit"]))    $result[] = $this->query["limit"];
            }else{
                return ;
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

        $this->queries[] = implode(" ", $query);

        if(isset($this->queries["union"])){
            $len = count($this->queries);
            $newArr = [$this->queries[$len - 3], $this->queries["union"], $this->queries[$len - 2]];
            unset($this->queries[$len - 3]);
            unset($this->queries["union"]);
            unset($this->queries[$len - 2]);
            $this->queries[] = implode(" ", $newArr);
        }
        $this->query = [];
    }

    /**
     * Execute all Queries.
     * * @param array $params for placeholders and values.
     * notes:
     * - [ ["p1" => "val1"], ["p2", "val2"] ]=> set val1 for :p1 in the first query, set val2 for :p2 in the secound one.
     * - each query should have its array, [ [values for 1st query], [2ed query]]
     * - for one query, just normal array ["user" => "username"] etc..
     * * @return array return the results.
     */
    public function callDB(array $params, bool $all = false) : array
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
            if(isset($params[0]) && is_array($params[0])){
                if(count($params) !== count($this->queries)){
                    $this->errorHandler(1011, "params: " . count($params) . " queries: " . count($this->queries) );
                }
                foreach ($this->queries as $key => $value) {
                    $result[] = $this->db::Fetch($value, $params[$key], $all);
                }
            }else{
                $result = $this->db::Fetch($this->queries[0], $params, $all);
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