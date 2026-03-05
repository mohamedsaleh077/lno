# LNO 
### LNO Not ORM
Simple Library to turn warp SQL in a smart way. Carries the heavy left from you when you
write SQL, without the unlimited complexity in the big Frameworks, just write a 
human-readable code and boom, you have SQL Query without Syntax errors and talk to your
Database with the most secure way and Best practicies. also Lightweight you know?

- on composer: https://packagist.org/packages/mohamedsaleh077/lno
- [AI Enhanced Documentation](./AI.md)

## Why I made LNO?
I just wanted to keep using pure PHP until I become good enough in OOP, MVC and most of 
backend concepts that Framework hide from you.
I started with a very basic one but I found it useless so I remade it.

## Features
- MySQL support by Default (I will add PostgreSQL later...).
- Auto Order for parts.
- RAW SQL with {} for escaping.
- Multi-Query Support.
- Advanced Nested where conditions.
- UNION Support.
- Issues handler, to protect your db.
- Method Chaining.
- Automatic Backticks.
- Rollback and Transactions for Multiple or single Query.
- Infinity of Joins.
- Insert-Select Support.
- Multiple Values support for Insert.
- Safe DELETE and UPDATE.
- Dependency Injection for DB Driver.

## Requirements
- PHP 8.2 or later.

## Quick Start
- Just use composer, install the package `mohamedsaleh077/lno`.
```json
...
"require": {
  "mohamedsaleh077/lno": "1.0.0",
...
```
or
```bash
 $ composer require mohamedsaleh077/lno
```
- import to your code.
```php
use mohamedsaleh077/lno/QueryBuilder;
use mohamedsaleh077/lno/MySQL_Driver;
```
- example for select statement
```php
$driver = new MySQL_Driver(); // for default db driver (MySQL)
$sql = new QueryBuilder($driver); 
$result = $sql->select("users")
                ->where(["id", "=", "id"])
                ->callDB(["id" => 12])
```
expected result for the SQL: `SELECT * FROM users WHERE id = :id`.

## All use cases Explaining
### Warnings Enable
warnings are enabled by default, to disable it:
```php
$sql->enableWarnings(false);
```

### Notes
- starting another statment like insert after a select or double selects will lead
to make a another query and will make two queries when you execute it.
- any conditions like where, join, limit, etc. without select will be ignored.
- writing two where (as an example) in the same query,
will lead to override it, join is excluded.
- passing the a non supported formating param will lead to unexcpected behaviours.
- for the last inserted id, `$driver::lastInsertId`

### SELECT Part
- you need to define the table (Optional, table and its alias)
```php
$sql->select("table name");

$sql->select(["tablename", "alias"]); // for aliasing
```
- when you are not defining the columns, it will use * as default.
```sql
SELECT * FROM `tablename` AS `alias`
```
- to define columns, make an array for them.
```php
$columns = [
    "col1", // `col1`
    "table2.col1", // `table2`.`col1`
    "col2" => "cl", // `col2` AS `cl`
    "table2.col2" => "acl", // `table2`.`col2` AS `acl`
    "table3.*" // `table3`.*
    "{COUNT(*)}" => "c", // COUNT(*) AS `c`
    "{COUNT(col2 > 5)}" // COUNT(col2 > 5)
];
```
- to write a Raw SQL, use `{your sql here}` as {} for escaping them and the Builder won't process them.
- SQL result:
```sql
SELECT 
    `col1`,
    `table2`.`col1`,
    `col2` AS `cl`, 
    `table2`.`col2` AS `acl`,
    `table3`.*,
    COUNT(*) AS `c`,
    COUNT(col2 > 5) 
FROM `tablename` AS `alias`
```

### WHERE Part
- for simple conditions:
```php
$sql->select("table")->where(["table.col", ">", "parm1"];
```
result
```sql
SELECT * FROM `table`
WHERE `table`.`col` > :param1
```

- for Advanced Nested Conditions
```php
$sql->select("table")
    ->where([ 
                ["table.col", ">", "parm1"], 
                "and" [
                        ["num", "not", "null"], 
                        "or", 
                        ["table.col", "<", "parm2"]
                ]   
            ];
```
result
```sql
SELECT * FROM `table`
WHERE (`table`.`col` > :param1) AND ( (`num` NOT NULL) OR (`table`.`col` < :param2) )
```

- Also where supports RAW SQL.
```php
$sql->select("table")->where(["table.col", "not between", "{15 AND 19}"];
```
result
```sql
SELECT * FROM `table`
WHERE `table`.`col` NOT BETWEEN (15 AND 19)
```

### JOIN Part
- accepts an array containt [table (=> "alias"), leftside, rightside] and
(left, right, full, inner is default)
```php
$sql->select(["users", "u"])
    ->join(["posts" => "p", "p.user_id", "u.id"])
    ->join(["comments" => "c", "c.user_id", "u.id"], "right");
```
result
```
SELECT * FROM `users` AS `u`
JOIN `posts` AS `p` ON `p`.`user_id` = `u`.`id`
RIGHT JOIN `comments` AS `c` ON `c`.`user_id` = `u`.`id`
```

### GROUP BY Part
- accepts column name
```php
$sql->select(["users"])
    ->groupby("col");
```
result
```sql
SELECT * FROM `users`
GROUP BY `col`
```

### HAVING Part
- just pass the condition, be aware about it since it is not processed.
```php
$sql->select(["users"])
    ->having("x > 5");
```
result
```sql
SELECT * FROM `users`
HAVING x > 5
```

### ORDER part
- pass an array of columns, columns are keys and (asc or desc) as value, for 
default, do not make it key-value.
- accepts RAW SQL by `{}`
```php
$sql->select(["users"])
    ->order(["col1" => "asc", "col2", "col3" => "desc"]);
```
result
```sql
SELECT * FROM `users`
ORDER BY `col1` ASC, `col2`, col3 DESC
```

### LIMIT Part
- accepts limit and offsset as integers
```php
$sql->select(["users"])
    ->limit(1, 15);
```
result
```sql
SELECT * FROM `users`
LIMIT 1, 15
```

### UNION Part
- accepts the word all (optional).
- add it between two SELECTs
```php
$sql->select(["users"])
    ->union("all")
    ->select(["uploads"]);
```
result
```sql
SELECT * FROM `users`
UNION ALL
SELECT * FROM `uploads`
```

## raw SQL
- if you need to write something is not supported. it will take the order that 
you called it with
```php
$sql->select(["users"])
    ->rawSQL("this is a raw sql")
    ->limit(1, 14);
```
result
```sql
SELECT * FROM `users`
this is a raw sql
LIMIT 1, 14
```

### INSERT statment
- you can use it with multiple values or with select.
- insert wont work without either values or select.
```php
$sql->insert("table_name", ["username", "fullname"])
    ->values(["u1", "f1"])
    ->values(["u2", "f2"])
    ->values(["u3", "f3"]);
])
```
result
```sql
INSERT INTO `table_name` (`username`, `fullname`)
VALUES
    (:u1, :f1),
    (:u2, :f2),
    (:u3, :f3)
```
or just combine with select.
```
$sql->insert(...)->select(...)->where(...)
```

### UPDATE  part
- accepts table name and array of columns.
- wont work without where.
```php
$sql->update("table", ["col1", "col2" => "c", "col3"]);
```
result
```sql
UPDATE `table` SET `col1` = :col1, `col2` = :c, `col3` = :col3
```

### DELETE Part
- accepts table name.
- must included with where.
```php
$sql->delete("users")
    ->where(["id", "=", "id"]);
```
result
```sql
DELETE FROM `users`
WHERE `id` = :id
```

### Running the Query
- To save the query (safty) use the method `saveQuery()` to add what you made to
the query list, it will be called automaticly when you start another statment or
using `callDB`.
- when you use CallDB, you need to pass an array for your params.
- if you made multiple queries, pass an array of arraies for each query, for example.
- to fetch all just pass true in secound param, fetching one is the default.
- `callDB` will make all the queries you made in a transaction, if one failed it will
rollback again.
```php
$oneQuery = [ "id" => 3, "col1" => "nnanan" ];
$manyQueries = [ ["c1" => 1 ], [], ["name" => "mohamed", "age" => 34] ];

$result = $sql->callDB($manyQueries, true); // for example
```
- results for each query will be in an array.
- each query will have a result array formed as:
```php
$result = [
    "ok" => 0, // success or fail, affected or not. db error will throw exception
    "lastID" => 0, // last inserted ID, in insert statment only
    "edited" => 0, // count of afftected raws
    "len" => 0, // length of results, when $all is true.
    "results" => [] // results for SELECT
];
```