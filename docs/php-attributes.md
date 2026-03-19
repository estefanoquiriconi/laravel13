# PHP Attributes in Laravel 13

Laravel 13 introduces native PHP 8 Attributes as a first-class alternative to class properties for configuring models, controllers, and queue jobs. This is a **non-breaking change** — existing property-based configuration continues to work.

This project uses PHP Attributes across the entire Mini Store API to demonstrate the new approach.

---

## Table of Contents

- [Model Attributes](#model-attributes)
  - [#\[Table\]](#table)
  - [#\[Fillable\]](#fillable)
  - [#\[Hidden\]](#hidden)
  - [#\[UseFactory\]](#usefactory)
- [Controller Attributes](#controller-attributes)
  - [#\[Middleware\]](#middleware)
  - [#\[Authorize\]](#authorize)
- [Queue Job Attributes](#queue-job-attributes)
  - [#\[Tries\]](#tries)
  - [#\[Timeout\]](#timeout)
  - [#\[Backoff\]](#backoff)
  - [#\[UniqueFor\]](#uniquefor)
  - [#\[FailOnTimeout\]](#failontimeout)
  - [#\[WithoutRelations\]](#withoutrelations)
- [Pros and Cons](#pros-and-cons)
- [Migration Strategy](#migration-strategy)

---

## Model Attributes

### #[Table]

Configures the model's table name, primary key, key type, auto-incrementing, timestamps, and date format.

**Before (Laravel 12):**

```php
class Product extends Model
{
    protected $table = 'products';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = true;
}
```

**After (Laravel 13):**

```php
use Illuminate\Database\Eloquent\Attributes\Table;

#[Table(key: 'id', keyType: 'string', incrementing: false)]
class Product extends Model
{
    // Class body is now focused on behavior, not configuration
}
```

**Available parameters:** `name`, `key`, `keyType`, `incrementing`, `timestamps`, `dateFormat`

**Used in this project:** [`app/Models/Product.php`](../app/Models/Product.php) — UUID primary key configuration.

---

### #[Fillable]

Defines which attributes are mass-assignable.

**Before (Laravel 12):**

```php
class Order extends Model
{
    protected $fillable = ['user_id', 'total', 'status'];
}
```

**After (Laravel 13):**

```php
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['user_id', 'total', 'status'])]
class Order extends Model
{
    //
}
```

**Used in this project:** [`Product`](../app/Models/Product.php), [`Order`](../app/Models/Order.php), [`OrderItem`](../app/Models/OrderItem.php), [`User`](../app/Models/User.php).

---

### #[Hidden]

Defines which attributes are hidden from array/JSON serialization.

**Before (Laravel 12):**

```php
class Product extends Model
{
    protected $hidden = ['created_at', 'updated_at'];
}
```

**After (Laravel 13):**

```php
use Illuminate\Database\Eloquent\Attributes\Hidden;

#[Hidden(['created_at', 'updated_at'])]
class Product extends Model
{
    //
}
```

**Used in this project:** [`app/Models/Product.php`](../app/Models/Product.php), [`app/Models/User.php`](../app/Models/User.php).

---

### #[UseFactory]

Explicitly links a model to its factory class, replacing the generic `HasFactory` PHPDoc annotation.

**Before (Laravel 12):**

```php
class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;
}
```

**After (Laravel 13):**

```php
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Database\Factories\ProductFactory;

#[UseFactory(ProductFactory::class)]
class Product extends Model
{
    use HasFactory;
}
```

This provides actual runtime binding instead of relying on a PHPDoc annotation that IDEs and static analyzers might ignore.

**Used in this project:** [`Product`](../app/Models/Product.php), [`Order`](../app/Models/Order.php).

---

## Controller Attributes

### #[Middleware]

Applies middleware at the class or method level, replacing the `middleware()` method call in the constructor.

**Before (Laravel 12):**

```php
class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }
}
```

Or in routes:

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/orders', [OrderController::class, 'index']);
});
```

**After (Laravel 13):**

```php
use Illuminate\Routing\Attributes\Controllers\Middleware;

#[Middleware('auth:sanctum')]
class OrderController extends Controller
{
    // No constructor needed for middleware
}
```

**Selective middleware with `except` / `only`:**

```php
// Before: had to define in constructor or split route groups
$this->middleware('auth:sanctum')->except(['index', 'show']);

// After: declarative, right on the class
#[Middleware('auth:sanctum', except: ['index', 'show'])]
class ProductController extends Controller
{
    //
}
```

**Used in this project:**
- [`ProductController`](../app/Http/Controllers/ProductController.php) — `#[Middleware('auth:sanctum', except: ['index', 'show'])]`
- [`OrderController`](../app/Http/Controllers/OrderController.php) — `#[Middleware('auth:sanctum')]`

---

### #[Authorize]

Applies authorization checks at the method level, replacing inline `$this->authorize()` calls or `can` middleware strings.

**Before (Laravel 12):**

```php
class OrderController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Order::class);

        return OrderResource::collection(/* ... */);
    }
}
```

Or via middleware:

```php
Route::get('/orders', [OrderController::class, 'index'])
    ->middleware('can:viewAny,App\Models\Order');
```

**After (Laravel 13):**

```php
use Illuminate\Routing\Attributes\Controllers\Authorize;

class OrderController extends Controller
{
    #[Authorize('viewAny', Order::class)]
    public function index(): AnonymousResourceCollection
    {
        // Authorization is handled before this method runs
        return OrderResource::collection(/* ... */);
    }
}
```

**Used in this project:** [`OrderController::index()`](../app/Http/Controllers/OrderController.php).

---

## Queue Job Attributes

Queue jobs benefit the most from attributes — they replace scattered class properties and methods with a clear, scannable block at the top of the class.

### Before (Laravel 12) — All properties mixed into the class:

```php
class ProcessOrder implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public $tries = 3;
    public $timeout = 120;
    public $backoff = 10;
    public $uniqueFor = 3600;
    public $failOnTimeout = true;

    public function __construct(
        #[WithoutRelations]
        public Order $order
    ) {}

    public function uniqueId(): int
    {
        return $this->order->id;
    }

    public function handle(): void
    {
        // ...
    }
}
```

### After (Laravel 13) — Configuration as attributes:

```php
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\FailOnTimeout;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Queue\Attributes\UniqueFor;
use Illuminate\Queue\Attributes\WithoutRelations;

#[Tries(3)]
#[Timeout(120)]
#[Backoff(10)]
#[UniqueFor(3600)]
#[FailOnTimeout]
#[WithoutRelations]
class ProcessOrder implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order) {}

    public function uniqueId(): int
    {
        return $this->order->id;
    }

    public function handle(): void
    {
        // ...
    }
}
```

**Used in this project:** [`app/Jobs/ProcessOrder.php`](../app/Jobs/ProcessOrder.php).

### Individual Attribute Reference

| Attribute | Replaces | Description |
|-----------|----------|-------------|
| `#[Tries(3)]` | `public $tries = 3` | Maximum number of retry attempts |
| `#[Timeout(120)]` | `public $timeout = 120` | Maximum execution time in seconds |
| `#[Backoff(10)]` | `public $backoff = 10` | Seconds to wait between retries |
| `#[UniqueFor(3600)]` | `public $uniqueFor = 3600` | How long (seconds) to keep the unique lock |
| `#[FailOnTimeout]` | `public $failOnTimeout = true` | Mark job as failed if it times out |
| `#[WithoutRelations]` | `#[WithoutRelations]` on property | Strip Eloquent relations before serialization (class-level) |

---

## Pros and Cons

### Advantages

1. **Declarative configuration** — All configuration is visible at the top of the class without scrolling through properties and constructor logic.

2. **Separation of concerns** — Configuration metadata is separated from business logic. The class body focuses on behavior.

3. **IDE and tooling support** — PHP Attributes are native language constructs with full IDE autocompletion, refactoring support, and static analysis.

4. **Scannable at a glance** — You can immediately see what a class does by looking at its attribute block:
   ```php
   #[Table(key: 'id', keyType: 'string', incrementing: false)]
   #[Fillable(['name', 'description', 'price', 'stock', 'slug'])]
   #[Hidden(['created_at', 'updated_at'])]
   class Product extends Model
   ```

5. **Non-breaking** — Completely optional. You can mix attributes with traditional properties in the same project, or migrate gradually.

6. **Self-documenting** — Attribute names clearly describe their purpose (`#[Tries(3)]` vs `public $tries = 3`).

### Disadvantages

1. **Requires PHP 8.3+** — Laravel 13 requires PHP 8.3 minimum. Projects on older PHP versions cannot use attributes.

2. **Learning curve** — Developers unfamiliar with PHP Attributes need to learn the new syntax and available attribute classes.

3. **Import verbosity** — Each attribute requires its own `use` import statement, which can make the imports section longer:
   ```php
   use Illuminate\Queue\Attributes\Backoff;
   use Illuminate\Queue\Attributes\FailOnTimeout;
   use Illuminate\Queue\Attributes\Timeout;
   use Illuminate\Queue\Attributes\Tries;
   use Illuminate\Queue\Attributes\UniqueFor;
   use Illuminate\Queue\Attributes\WithoutRelations;
   ```

4. **Two ways to do the same thing** — Having both attributes and properties available can lead to inconsistency across a codebase if not standardized.

5. **Limited discoverability** — New developers might not know which attributes are available without consulting the documentation.

6. **No dynamic values** — Attributes must use compile-time constants. You cannot use environment variables or runtime expressions:
   ```php
   // This is NOT possible:
   #[Timeout(config('queue.timeout'))]  // ❌ Won't work
   #[Timeout(120)]                       // ✅ Must be a constant
   ```

---

## Migration Strategy

1. **New code** — Use attributes for all new models, controllers, and jobs.
2. **Existing code** — Convert when you're already touching the file. Don't batch-convert multiple files in one PR.
3. **Be consistent** — Pick one approach per class. Don't mix `#[Fillable]` with `protected $hidden` on the same model.
4. **Dynamic values** — Keep using properties when configuration depends on runtime values (environment variables, config files).

---

## Files Using Attributes in This Project

| File | Attributes Used |
|------|----------------|
| `app/Models/Product.php` | `#[Table]`, `#[Fillable]`, `#[Hidden]`, `#[UseFactory]` |
| `app/Models/Order.php` | `#[Fillable]`, `#[UseFactory]` |
| `app/Models/OrderItem.php` | `#[Fillable]` |
| `app/Models/User.php` | `#[Fillable]`, `#[Hidden]` |
| `app/Http/Controllers/ProductController.php` | `#[Middleware]` with `except` |
| `app/Http/Controllers/OrderController.php` | `#[Middleware]`, `#[Authorize]` |
| `app/Jobs/ProcessOrder.php` | `#[Tries]`, `#[Timeout]`, `#[Backoff]`, `#[UniqueFor]`, `#[FailOnTimeout]`, `#[WithoutRelations]` |
