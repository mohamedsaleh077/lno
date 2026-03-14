# Lno Query Builder: High-Performance Fluent SQL for PHP

**Lno** is a lightweight, high-performance PHP Query Builder designed for developers who need the power of complex SQL without the overhead of heavy ORMs like Eloquent or Doctrine. It provides a fluent, expressive API for building sophisticated queries while maintaining absolute control over the generated SQL.

---

### 🚀 Introduction & Value Proposition

In the world of PHP, developers often face a choice: write raw SQL (which is fast but error-prone) or use a heavy ORM (which is safe but can be slow and memory-intensive). **Lno** bridges this gap.

#### Why Choose Lno?
- **Lightweight & Fast:** Zero hydration overhead. Lno returns clean arrays, making it ideal for high-performance applications and real-time reporting.
- **Fluent Logic:** Build complex `WHERE` clauses with nested `AND/OR` logic using simple, intuitive array structures.
- **Advanced SQL Support:** Native support for Common Table Expressions (CTEs), nested subqueries, and `INSERT SELECT` operations that many micro-libraries lack.
- **Atomic Operations:** Built-in transaction management ensures that multi-query operations are executed safely or not at all.

---

### 📦 Installation & Configuration

#### Installation via Composer
```bash
composer require mohamedsaleh077/lno
```

#### Manual Installation
If you prefer not to use Composer, you can include the source files directly:
```php
require_once 'src/DatabaseInterface.php';
require_once 'src/QueryBuilderHelper.php';
require_once 'src/OP.php';
require_once 'src/QueryBuilder.php';
```

#### Initializing the Library
Lno requires an implementation of `DatabaseInterface`. You can use the provided `MySQL_Driver` or create your own.

```php
use Mohamedsaleh077\Lno\QueryBuilder;
use Mohamedsaleh077\Lno\MySQL;

// Initialize the driver with a path to your config.ini
$db = new MySQL('/config.ini'); 
$qb = new QueryBuilder($db);
```

---

### 🛠 Mastering Complex Queries

#### 1. Advanced SELECTs & Nested Logic
Lno excels at building complex `WHERE` clauses. You can nest arrays to create grouped logic.

```php
$results = $qb->select('orders', ['id', 'total', 'status'])
    ->where([
        ['status', '=', 'shipped'],
        'AND',
        [
            ['total', '>', 100],
            'OR',
            ['customer_type', '=', 'VIP']
        ]
    ])
    ->order(['created_at' => 'DESC'])
    ->limit(20)
    ->callDB(true); // true returns all results (fetchAll)
```

#### 2. Common Table Expressions (CTEs)
Use `withSQL()` to build high-performance `WITH` clauses for complex data processing.

```php
$sub = $qb->subQuery()
    ->select('sales', ['region', '{SUM(amount)}' => 'total_sales'])
    ->groupBy('region');

$qb->withSQL([
    'regional_revenue' => "{$sub}"
])
->select('regional_revenue')
->where(['total_sales', '>', 50000])
->callDB(true);
```

#### 3. Subqueries in SELECT, WHERE, and ORDER BY
The `subQuery()` method allows you to create independent builder instances that can be injected into your main query as strings.

```php
$latestOrder = $qb->subQuery()
    ->select('orders', ['id'])
    ->where(['user_id', '=', '{users.id}'])
    ->order(['created_at' => 'DESC'])
    ->limit(1);

$users = $qb->select('users', ['id', 'username', "({$latestOrder})" => 'latest_order_id'])
    ->where(['status', '=', 'active'])
    ->callDB(true);
```

#### 4. INSERT SELECT & Batch Operations
Pipe data between tables efficiently or perform multi-row inserts.

**INSERT SELECT:**
```php
$qb->insert('archive_users', ['id', 'username', 'email'])
    ->select('users', ['id', 'username', 'email'])
    ->where(['deleted_at', 'IS NOT', '{NULL}'])
    ->callDB();
```

**Batch INSERT:**
```php
$qb->insert('logs', ['level', 'message'])
    ->values(['INFO', 'User logged in'])
    ->values(['WARN', 'Failed login attempt'])
    ->values(['ERROR', 'Database timeout'])
    ->callDB();
```

#### 5. Raw SQL Integration
Safely mix raw SQL while maintaining parameter binding for your own variables. Wrap raw SQL fragments in `{}` to tell Lno not to escape or parameterize them.

```php
$qb->select('products', ['id', 'name', '{PRICE * 1.1}' => 'inflated_price'])
    ->where(['category_id', '=', 5])
    ->rawSQL("AND stock_count > 0")
    ->callDB(true);
```

---

### 🛡 Transaction Management

Lno ensures **Atomicity** through its `callDB()` flow. Every operation added to the builder before calling `callDB()` is treated as part of a single database transaction.

1. **Queuing:** Methods like `insert()`, `update()`, or `delete()` queue queries.
2. **Execution:** `callDB()` begins a transaction.
3. **Safety:** It iterates through all queued queries. If any query fails, an exception is thrown and the transaction is **rolled back**.
4. **Completion:** If all queries succeed, the transaction is **committed**.

```php
// Transferring balance between accounts
$qb->update('accounts', ['balance' => 500])->where(['id', '=', 1]);
$qb->update('accounts', ['balance' => 1500])->where(['id', '=', 2]);

// Both updates are executed within a single transaction
$qb->callDB();
```

---

### 🔒 Security & Parameter Binding

Lno takes security seriously by implementing an intelligent **Automatic Parameterization** system.

- **Auto-binding:** Every value passed to `where()`, `values()`, or `update()` is automatically converted to a PDO placeholder (`:p0`, `:p1`, etc.).
- **Parameter Reordering:** When building complex queries with subqueries, the order in which parameters are defined might not match their appearance in the final SQL. Lno's `paramSetter` uses regex to scan the final SQL and reorder the parameter array to match the execution plan perfectly.
- **SQL Injection Prevention:** By enforcing PDO prepared statements for all user-provided data, Lno effectively mitigates SQL injection risks.

---

### 📊 Comparison with Eloquent/Doctrine

| Feature | Lno | Eloquent / Doctrine |
| :--- | :--- | :--- |
| **Performance** | **Ultra-Fast** (Direct PDO, Array output) | Slower (Object hydration, Active Record overhead) |
| **Memory Usage** | **Minimal** | High (due to object management) |
| **Complex SQL** | Native support for CTEs & Subqueries | Often requires raw queries or complex wrappers |
| **Learning Curve** | Low (SQL-like fluent API) | High (Learning the ORM's specific DSL) |
| **Reporting** | Excellent for heavy data processing | Can struggle with large datasets |
| **Setup** | Zero-config, lightweight | Complex configuration and mapping |

---

### 🤝 Contribution Guide

We welcome contributions! To maintain code quality, please follow these guidelines:

#### Architecture Overview
- **`OP.php`:** The engine. Handles query string concatenation, parameter collection, and the `callDB()` transaction flow.
- **`QueryBuilder.php`:** The user-facing API. Responsible for translating fluent methods into structured arrays for `OP`.
- **`QueryBuilderHelper.php`:** A trait containing utility methods for dot-notation parsing, aliasing validation, and error/warning handling.

#### Standards
- Follow PSR-12 coding standards.
- Ensure all new features include unit tests in the `Testing/` directory.
- Use `errorHandler()` for critical syntax issues and `warningHandler()` for non-breaking suggestions.

---

*Documentation generated by AI for Lno Query Builder.*
