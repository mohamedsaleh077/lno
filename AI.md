#  LNO (Lightweight Native Objects)

### "SQL, but smarter. Not an ORM, just better."

**LNO** is a lightweight PHP library designed to bridge the gap between pure SQL and complex ORMs. It handles the heavy lifting of SQL syntax, escaping, and security, while keeping your code human-readable and efficient.

---

## ✨ Key Features

* **Intuitive Method Chaining:** Write queries that look like English.
* **Security First:** Automatic escaping and built-in protection for `UPDATE` and `DELETE` (no accidental "truncate everything").
* **Smart SQL Engine:** Automatic backticks, clause ordering, and dependency injection for DB drivers.
* **Advanced Logic:** Supports nested `WHERE` conditions, complex `JOINS`, and `UNION`.
* **Atomic Transactions:** Integrated `callDB` handles transactions and rollbacks automatically for multi-query operations.
* **Zero Dependencies:** Extremely lightweight and fast.

---

## 🛠 Installation & Setup

### Requirements

* **PHP:** 8.2 or higher.
* **Database:** MySQL (PostgreSQL support coming soon).

### Install via Composer

```bash
composer require mohamedsaleh077/lno

```

### Quick Start

```php
use mohamedsaleh077\lno\QueryBuilder;
use mohamedsaleh077\lno\MySQL_Driver;

$driver = new MySQL_Driver(); 
$db = new QueryBuilder($driver); 

// Simple Select
$result = $db->select("users")
             ->where(["id", "=", "user_id"])
             ->callDB(["user_id" => 12]);

// Resulting SQL: SELECT * FROM `users` WHERE `id` = :user_id

```

---

## 📖 Detailed Usage Guide

### 1. The `SELECT` Clause

LNO handles aliases and raw expressions effortlessly using `{}` for escaping.

```php
$columns = [
    "username",                      // `username`
    "p.title" => "post_title",      // `p`.`title` AS `post_title`
    "{COUNT(*)}" => "total"         // Raw: COUNT(*) AS `total`
];

$db->select(["users", "u"], $columns);
// SQL: SELECT `username`, `p`.`title` AS `post_title`, COUNT(*) AS `total` FROM `users` AS `u`

```

### 2. Advanced `WHERE` Conditions

Supports simple arrays or complex nested logic.

```php
// Complex nested: WHERE (status = 1) AND (age < 18 OR role = 'guest')
$db->select("users")->where([
    ["status", "=", "active"],
    "AND", [
        ["age", "<", "limit"],
        "OR",
        ["role", "=", "guest"]
    ]
]);

```

### 3. Joins & Relationships

```php
$db->select(["posts", "p"])
   ->join(["users" => "u", "p.user_id", "u.id"], "inner")
   ->join(["comments" => "c", "c.post_id", "p.id"], "left");

```

### 4. Insert, Update, & Delete

LNO ensures your write operations are structured and safe.

| Action | Example Code |
| --- | --- |
| **Insert** | `$db->insert("users", ["name", "email"])->values(["John", "j@ex.com"]);` |
| **Update** | `$db->update("users", ["status"])->where(["id", "=", "id"]);` |
| **Delete** | `$db->delete("users")->where(["id", "=", "id"]);` |

> ⚠️ **Note:** `UPDATE` and `DELETE` require a `where()` clause to execute, preventing accidental data loss.

---

## ⚡ Database Execution (`callDB`)

The `callDB` method is the brain of LNO. It executes your queries within a **Transaction** context.

```php
// For a single query
$res = $db->callDB(["id" => 5]);

// For multiple queries (Automatic Transaction/Rollback)
$params = [
    ["id" => 1],           // Params for Query 1
    ["status" => "active"] // Params for Query 2
];
$res = $db->callDB($params, true); // true to fetch all rows

```

### Response Structure

Every execution returns a structured array:

```php
[
    "ok"      => (bool),   // Success status
    "lastID"  => (int),    // Last Inserted ID (if applicable)
    "edited"  => (int),    // Number of affected rows
    "len"     => (int),    // Result count
    "results" => (array)   // Data rows from SELECT
]

```

---

## 🛡 Security & Best Practices

* **Escaping:** Use `{}` to pass raw SQL fragments safely when needed.
* **Warnings:** Enabled by default. Use `$db->enableWarnings(false)` to silence.
* **Transactions:** LNO automatically rolls back all queries in a `callDB` batch if one fails.

---

## 🤝 Contributing

Found a bug or want to add PostgreSQL support?

1. Fork the repo.
2. Create a feature branch.
3. Submit a Pull Request.

**Created by:** [Mohamed Saleh](https://mohamedsaleh077.github.io/)