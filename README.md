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
I started wit h a very basic one but I found it useless so I remade it.

## NEW Features
- Add SubQuery Support.
- Add auto generating for params and order them.
- Add mock files for testing.
- Refactoring code by separating responsibilities.
- Remove UNION.
- Add WITH support to SQL.
- Add PostgreSQL support.

## Features
- MySQL support by Default (I will add PostgreSQL later...).
- Auto Order for parts.
- RAW SQL with {} for escaping.
- Multi-Query Support.
- Advanced Nested where conditions.
- ~~UNION Support.~~
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
```bash
 $ composer require mohamedsaleh077/lno
```
- import to your code.
```php
use mohamedsaleh077/lno/QueryBuilder;
use mohamedsaleh077/lno/MySQL;
use mohamedsaleh077/lno/PostgreSQL;
```
- example for select statement
```php
$mysqlDriver = new MySQL();
$postgresDriver = new PostgreSQL();

$sql = new QueryBuilder($mysqlDriver); 
$result = $sql->select("users")
                ->where(["id", "=", 2])
                ->callDB()
```
expected result for the SQL: `SELECT * FROM users WHERE id = :p1`.

**Note:** Do not forget to add `.env` in the root directory or in parent directory.

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
$sql$t->select("table")->where(["table.col", ">", 34]);
```
result
```sql
SELECT * FROM `table`
WHERE `table`.`col` > :p2
```

- for Advanced Nested Conditions
```php
$t->select("table")
    ->where([
        ["table.col", ">", 15],
        "and", [
        ["num", "not", "null"],
        "or",
        ["table.col", "<", "value"]
        ]
    ])
```
result
```sql
SELECT * FROM `table` 
WHERE (`table`.`col` > :p2) AND ((`num` NOT NULL)  OR (`table`.`col` < :p4))
```

- Also where supports RAW SQL.
```php
$sql->select("table")->where(["table.col", "=", "{RAWRAWRAW SQLLL}"]);
```
result
```sql
SELECT * FROM `table` 
WHERE `table`.`col` = (RAWRAWRAW SQLLL)
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

### UNION Part (Deprecated)
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

### WITH Part
- read: https://dev.mysql.com/doc/refman/8.4/en/with.html
```php
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
```
result
```sql
WITH 
    cta1 AS ( SELECT * FROM `test` WHERE `id` = :p1 ),
    cta2 AS ( SELECT * FROM `test` WHERE `id` = :p1 ) 
SELECT * FROM `table`
WHERE `id` = :p2 
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
$t->insert("table_name", ["username", "fullname"])
    ->values(["u1", "f1"])
    ->values(["u2", "f2"])
    ->values(["u3", "f3"])
```
result
```sql
INSERT INTO `table_name` (`username`, `fullname`)
VALUES  
    (:p2, :p3),
    (:p4, :p5),
    (:p6, :p7)
```
or just combine with select.
```
$sql->insert(...)->select(...)->where(...)
```

### UPDATE  part
- accepts table name and array of columns.
- wont work without where.
```php
$t->update("table", ["t"=>"value"])->where(["i", "=", 5]);
```
result
```sql
UPDATE `table` 
SET `t` = :p0 
WHERE `i` = :p1
```

### DELETE Part
- accepts table name.
- must included with where.
```php
$sql->delete("users")
    ->where(["id", "=", 2]);
```
result
```sql
DELETE FROM `users`
WHERE `id` = :p2
```

### Running the Query
- All Params are generated and assigend to values automaticly and beign passed
while running the query.
- you just need to define `true` for fetching all results.
```php
$result = $sql->callDB(true); // for example
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