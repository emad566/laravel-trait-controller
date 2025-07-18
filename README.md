# Senior Laravel Interview Questions & Answers

Below are 100 senior-level Laravel questions, each with a detailed answer and example. Each question is marked with a difficulty:
- **A**: Easy
- **B**: Medium
- **C**: Hard

---

### 1. (A) What is Dependency Injection in Laravel and how is it used?
**Answer:**
Dependency Injection is a design pattern where dependencies (objects or services) are provided to a class rather than being created inside the class. In Laravel, you can inject dependencies via the constructor or method parameters, and Laravel’s service container will automatically resolve them.

**Example:**
```php
class UserController extends Controller {
    public function __construct(UserRepository $repo) {
        $this->repo = $repo;
    }
}
```

---

### 2. (A) How do you define and use middleware in Laravel?
**Answer:**
Middleware filters HTTP requests entering your application. You define middleware using `php artisan make:middleware`, register it in `app/Http/Kernel.php`, and attach it to routes.

**Example:**
```php
// app/Http/Middleware/CheckAge.php
public function handle($request, Closure $next) {
    if ($request->age < 18) {
        return redirect('home');
    }
    return $next($request);
}
```

---

### 3. (B) Explain the Repository Pattern and how you would implement it in Laravel.
**Answer:**
The Repository Pattern abstracts data access logic, making your code more testable and maintainable. You define an interface and a concrete class, then inject the interface where needed.

**Example:**
```php
interface UserRepositoryInterface {
    public function all();
}
class EloquentUserRepository implements UserRepositoryInterface {
    public function all() {
        return User::all();
    }
}
// In a service provider:
$this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
```

---

### 4. (B) How does Laravel’s event system work? Give an example.
**Answer:**
Laravel’s event system allows you to subscribe and listen for events in your application. You define events and listeners, then fire events using the `event()` helper.

**Example:**
```php
// Event
class OrderShipped implements ShouldBroadcast {
    public $order;
    public function __construct(Order $order) {
        $this->order = $order;
    }
}
// Listener
class SendShipmentNotification {
    public function handle(OrderShipped $event) {
        // send notification
    }
}
// Fire event
event(new OrderShipped($order));
```

---

### 5. (A) What is Eloquent ORM and how does it differ from Query Builder?
**Answer:**
Eloquent is Laravel’s ActiveRecord implementation for working with databases using models. Query Builder provides a more direct, fluent interface for building SQL queries. Eloquent is object-oriented, while Query Builder is more procedural.

**Example:**
```php
// Eloquent
$users = User::where('active', 1)->get();
// Query Builder
$users = DB::table('users')->where('active', 1)->get();
```

---

### 6. (B) How do you use Laravel’s Service Container for binding and resolving classes?
**Answer:**
The Service Container is a powerful tool for managing class dependencies. You can bind interfaces to implementations and resolve them automatically.

**Example:**
```php
// Binding in a service provider
$this->app->bind(PaymentGateway::class, StripePaymentGateway::class);
// Resolving
gateway = app(PaymentGateway::class);
```

---

### 7. (C) How would you implement a custom validation rule in Laravel?
**Answer:**
You can create a custom rule using `php artisan make:rule`, then implement the `passes` and `message` methods.

**Example:**
```php
// app/Rules/Uppercase.php
class Uppercase implements Rule {
    public function passes($attribute, $value) {
        return strtoupper($value) === $value;
    }
    public function message() {
        return 'The :attribute must be uppercase.';
    }
}
// Usage in FormRequest
'random_field' => [new Uppercase],
```

---

### 8. (B) What is a Service Provider in Laravel and what is its purpose?
**Answer:**
Service Providers are the central place to configure and bind classes into the service container. They are loaded on every request and are used to bootstrap application services.

**Example:**
```php
// app/Providers/AppServiceProvider.php
public function register() {
    $this->app->bind('SomeService', function($app) {
        return new SomeService();
    });
}
```

---

### 9. (C) How do you handle database transactions in Laravel?
**Answer:**
You can use the `DB::transaction()` method to run a set of operations within a transaction. If any operation fails, the transaction is rolled back.

**Example:**
```php
DB::transaction(function () {
    User::create([...]);
    Profile::create([...]);
});
```

---

### 10. (C) Explain how to use Laravel’s Policy and Gate for authorization.
**Answer:**
Policies are classes that organize authorization logic around a model. Gates are closures that determine if a user is authorized to perform a given action. You register policies in `AuthServiceProvider` and use them via the `can` method or middleware.

**Example:**
```php
// Policy
public function update(User $user, Post $post) {
    return $user->id === $post->user_id;
}
// Usage
if ($user->can('update', $post)) { /* ... */ }
```

---

### 11. (B) How do you use Laravel’s Task Scheduling feature?
**Answer:**
Laravel’s task scheduling allows you to fluently and expressively define command schedule within your application using the `app/Console/Kernel.php` file. You use the `schedule` method to define scheduled tasks.

**Example:**
```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->command('emails:send')->daily();
}
```

---

### 12. (A) What is the difference between `hasOne` and `belongsTo` relationships in Eloquent?
**Answer:**
`hasOne` defines a one-to-one relationship where the foreign key is on the related model. `belongsTo` defines the inverse, where the foreign key is on the current model.

**Example:**
```php
// User has one Profile
public function profile() { return $this->hasOne(Profile::class); }
// Profile belongs to User
public function user() { return $this->belongsTo(User::class); }
```

---

### 13. (B) How do you use Laravel’s broadcasting system for real-time events?
**Answer:**
Broadcasting allows you to share the same event names between your server-side Laravel application and your client-side JavaScript application using channels and events.

**Example:**
```php
// Event implements ShouldBroadcast
class OrderShipped implements ShouldBroadcast {
    public function broadcastOn() {
        return new Channel('orders');
    }
}
```

---

### 14. (C) How would you implement multi-authentication (multi-guard) in Laravel?
**Answer:**
Multi-authentication allows different user types (e.g., admins, users) to authenticate separately. You define multiple guards in `config/auth.php` and use middleware to protect routes.

**Example:**
```php
// config/auth.php
'guards' => [
    'web' => [...],
    'admin' => [
        'driver' => 'session',
        'provider' => 'admins',
    ],
],
// Route
Route::middleware('auth:admin')->group(function () { ... });
```

---

### 15. (A) How do you use Laravel’s soft deletes?
**Answer:**
Soft deletes allow you to keep records in the database but mark them as deleted. Use the `SoftDeletes` trait in your model and add a `deleted_at` column.

**Example:**
```php
use Illuminate\Database\Eloquent\SoftDeletes;
class Post extends Model {
    use SoftDeletes;
}
// Soft delete
dbPost::find(1)->delete();
// Restore
Post::withTrashed()->find(1)->restore();
```

---

### 16. (B) How do you use Laravel’s API Resource classes?
**Answer:**
API Resources transform your models and collections into JSON. Create a resource with `php artisan make:resource`, then return it from your controller.

**Example:**
```php
// app/Http/Resources/UserResource.php
public function toArray($request) {
    return [
        'id' => $this->id,
        'name' => $this->name,
    ];
}
// Usage
return new UserResource($user);
```

---

### 17. (C) How would you implement a custom Artisan command?
**Answer:**
Use `php artisan make:command` to generate a new command class. Implement the `handle` method with your logic, and register the command in `Kernel.php`.

**Example:**
```php
// app/Console/Commands/SendReminders.php
class SendReminders extends Command {
    protected $signature = 'reminders:send';
    public function handle() {
        // logic here
    }
}
```

---

### 18. (B) What is the difference between `pluck` and `select` in Eloquent?
**Answer:**
`pluck` retrieves a single column’s values as a collection, while `select` specifies which columns to retrieve in the query result.

**Example:**
```php
// Pluck
$emails = User::pluck('email');
// Select
$users = User::select('id', 'email')->get();
```

---

### 19. (C) How do you use Laravel’s query scopes?
**Answer:**
Query scopes allow you to define common sets of query constraints in your model. Use `scope` prefix for local scopes.

**Example:**
```php
// In User model
public function scopeActive($query) {
    return $query->where('active', 1);
}
// Usage
$activeUsers = User::active()->get();
```

---

### 20. (A) How do you use Laravel’s `@inject` directive in Blade?
**Answer:**
The `@inject` directive allows you to inject a service from the service container directly into your Blade view.

**Example:**
```blade
@inject('metrics', 'App\Services\MetricsService')
<div>{{ $metrics->getUserCount() }}</div>
```

---

### 21. (B) How do you use Laravel’s `when` method in query building?
**Answer:**
The `when` method allows you to conditionally add clauses to a query based on a given value or condition.

**Example:**
```php
$query = User::query();
$query->when(request('role'), function ($q, $role) {
    return $q->where('role', $role);
});
$users = $query->get();
```

---

### 22. (C) How would you implement a custom Blade directive?
**Answer:**
You can create custom Blade directives using the `Blade::directive` method, typically in a service provider’s `boot` method.

**Example:**
```php
// In AppServiceProvider
Blade::directive('datetime', function ($expression) {
    return "<?php echo ($expression)->format('m/d/Y H:i'); ?>";
});
// Usage in Blade
@datetime($user->created_at)
```

---

### 23. (B) What is the difference between `hasManyThrough` and `hasOneThrough` relationships?
**Answer:**
`hasManyThrough` provides a shortcut for accessing distant relations via an intermediate model, returning many results. `hasOneThrough` returns a single result through an intermediate model.

**Example:**
```php
// Country has many Posts through Users
public function posts() {
    return $this->hasManyThrough(Post::class, User::class);
}
```

---

### 24. (C) How do you use Laravel’s job batching feature?
**Answer:**
Job batching allows you to dispatch multiple jobs as a batch and then perform actions when all jobs have completed.

**Example:**
```php
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;

Bus::batch([
    new ProcessPodcast($podcast),
    new OptimizePodcast($podcast),
])->then(function (Batch $batch) {
    // All jobs completed...
})->dispatch();
```

---

### 25. (A) How do you use Laravel’s `withCount` method?
**Answer:**
The `withCount` method adds a `{relation}_count` column to your results, showing the number of related models.

**Example:**
```php
$users = User::withCount('posts')->get();
// $users[0]->posts_count
```

---

### 26. (B) How do you use Laravel’s `FormRequest` for validation?
**Answer:**
Create a custom request with `php artisan make:request`, define your rules, and type-hint it in your controller method. Laravel will automatically validate the request.

**Example:**
```php
// app/Http/Requests/StoreUserRequest.php
public function rules() {
    return ['name' => 'required', 'email' => 'required|email'];
}
// Controller
public function store(StoreUserRequest $request) {
    // Validated data: $request->validated()
}
```

---

### 27. (C) How do you use Laravel’s `tap` helper function?
**Answer:**
The `tap` function allows you to perform actions on an object and then return the object itself, useful for method chaining.

**Example:**
```php
$user = tap(User::first(), function ($user) {
    $user->update(['last_login' => now()]);
});
```

---

### 28. (B) How do you use Laravel’s `assertDatabaseHas` in testing?
**Answer:**
`assertDatabaseHas` asserts that a given record exists in the database during a test.

**Example:**
```php
public function test_user_created() {
    $user = User::factory()->create(['email' => 'test@example.com']);
    $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
}
```

---

### 29. (C) How do you use Laravel’s `macroable` trait?
**Answer:**
The `Macroable` trait allows you to add custom methods (macros) to a class at runtime.

**Example:**
```php
use Illuminate\Support\Str;
Str::macro('initials', function ($value) {
    return collect(explode(' ', $value))->map(fn($word) => strtoupper($word[0]))->implode('');
});
// Usage
Str::initials('John Doe'); // JD
```

---

### 30. (A) How do you use Laravel’s `old` helper in Blade templates?
**Answer:**
The `old` helper retrieves the previously flashed input value from the session, useful for repopulating forms after validation errors.

**Example:**
```blade
<input type="text" name="name" value="{{ old('name') }}">
```

---

### 31. (B) How do you use Laravel’s `whereHas` and `whereDoesntHave` methods?
**Answer:**
`whereHas` filters the main query based on the existence of a relationship, while `whereDoesntHave` filters based on the absence of a relationship.

**Example:**
```php
// Users who have posts
$users = User::whereHas('posts')->get();
// Users who don't have posts
$users = User::whereDoesntHave('posts')->get();
```

---

### 32. (C) How do you implement Laravel’s rate limiting?
**Answer:**
Rate limiting can be implemented using middleware or the `RateLimiter` facade. You can limit requests per minute, hour, etc.

**Example:**
```php
// In middleware
public function handle($request, Closure $next) {
    if (RateLimiter::tooManyAttempts('key', 60)) {
        return response('Too many requests', 429);
    }
    RateLimiter::hit('key');
    return $next($request);
}
```

---

### 33. (A) How do you use Laravel’s `firstOrCreate` and `updateOrCreate` methods?
**Answer:**
`firstOrCreate` finds the first record matching the attributes or creates it if none exists. `updateOrCreate` finds and updates the first record or creates it if none exists.

**Example:**
```php
// First or create
$user = User::firstOrCreate(['email' => 'john@example.com'], ['name' => 'John']);
// Update or create
$user = User::updateOrCreate(['email' => 'john@example.com'], ['name' => 'John']);
```

---

### 34. (B) How do you use Laravel’s `withPivot` method in many-to-many relationships?
**Answer:**
`withPivot` allows you to access additional columns from the pivot table in many-to-many relationships.

**Example:**
```php
// In User model
public function roles() {
    return $this->belongsToMany(Role::class)->withPivot('expires_at');
}
// Usage
$user->roles->first()->pivot->expires_at;
```

---

### 35. (C) How do you implement Laravel’s custom validation rules with parameters?
**Answer:**
You can create custom validation rules that accept parameters using the `Rule` class and implementing the `passes` method.

**Example:**
```php
class Uppercase implements Rule {
    public function passes($attribute, $value) {
        return strtoupper($value) === $value;
    }
    public function message() {
        return 'The :attribute must be uppercase.';
    }
}
// Usage
'field' => [new Uppercase],
```

---

### 36. (B) How do you use Laravel’s `chunk` method for processing large datasets?
**Answer:**
The `chunk` method processes large datasets in smaller chunks to avoid memory issues.

**Example:**
```php
User::chunk(100, function ($users) {
    foreach ($users as $user) {
        // Process each user
    }
});
```

---

### 37. (A) How do you use Laravel’s `config` helper function?
**Answer:**
The `config` helper retrieves values from configuration files. You can also set values using the same function.

**Example:**
```php
// Get config value
$appName = config('app.name');
// Set config value
config(['app.debug' => true]);
```

---

### 38. (C) How do you implement Laravel’s custom middleware with parameters?
**Answer:**
You can pass parameters to middleware by defining them in the middleware signature and using them in the `handle` method.

**Example:**
```php
// app/Http/Middleware/CheckRole.php
public function handle($request, Closure $next, $role) {
    if (!auth()->user()->hasRole($role)) {
        abort(403);
    }
    return $next($request);
}
// Usage in routes
Route::middleware('check.role:admin')->group(function () { ... });
```

---

### 39. (B) How do you use Laravel’s `whereIn` and `whereNotIn` methods?
**Answer:**
`whereIn` filters records where a column value is in a given array, while `whereNotIn` filters records where a column value is not in a given array.

**Example:**
```php
// Users with specific IDs
$users = User::whereIn('id', [1, 2, 3])->get();
// Users not with specific IDs
$users = User::whereNotIn('id', [1, 2, 3])->get();
```

---

### 40. (C) How do you implement Laravel’s custom collection macros?
**Answer:**
You can add custom methods to Laravel collections using the `Collection::macro` method, typically in a service provider.

**Example:**
```php
// In AppServiceProvider
Collection::macro('toUpper', function () {
    return $this->map(function ($value) {
        return Str::upper($value);
    });
});
// Usage
$collection = collect(['hello', 'world'])->toUpper();
```

---

### 41. (B) How do you use Laravel’s `with` method for eager loading relationships?
**Answer:**
The `with` method allows you to eager load relationships to avoid the N+1 query problem by loading related models in a single query.

**Example:**
```php
// Eager load posts with users
$users = User::with('posts')->get();
// Access posts without additional queries
foreach ($users as $user) {
    echo $user->posts->count();
}
```

---

### 42. (C) How do you implement Laravel’s custom database connections?
**Answer:**
You can define custom database connections in `config/database.php` and use them in your models or queries.

**Example:**
```php
// config/database.php
'connections' => [
    'mysql' => [...],
    'mysql_readonly' => [
        'driver' => 'mysql',
        'host' => env('DB_READONLY_HOST'),
        // ... other config
    ],
],
// Usage
$users = DB::connection('mysql_readonly')->table('users')->get();
```

---

### 43. (A) How do you use Laravel’s `dd` and `dump` helper functions?
**Answer:**
`dd` (dump and die) outputs the contents of a variable and stops execution. `dump` outputs the contents but continues execution.

**Example:**
```php
// Dump and die
dd($user);
// Dump and continue
dump($user);
```

---

### 44. (B) How do you use Laravel’s `whereBetween` and `whereNotBetween` methods?
**Answer:**
`whereBetween` filters records where a column value is between two values, while `whereNotBetween` filters records where a column value is not between two values.

**Example:**
```php
// Users created between dates
$users = User::whereBetween('created_at', ['2023-01-01', '2023-12-31'])->get();
// Users not created between dates
$users = User::whereNotBetween('created_at', ['2023-01-01', '2023-12-31'])->get();
```

---

### 45. (C) How do you implement Laravel’s custom cache drivers?
**Answer:**
You can create custom cache drivers by implementing the `Illuminate\Contracts\Cache\Store` interface and registering it in a service provider.

**Example:**
```php
// Custom cache driver
class CustomCacheStore implements Store {
    public function get($key) { /* implementation */ }
    public function put($key, $value, $seconds) { /* implementation */ }
    // ... other methods
}
// Register in service provider
$this->app->singleton('cache.store.custom', function () {
    return new CustomCacheStore();
});
```

---

### 46. (B) How do you use Laravel’s `whereNull` and `whereNotNull` methods?
**Answer:**
`whereNull` filters records where a column value is NULL, while `whereNotNull` filters records where a column value is not NULL.

**Example:**
```php
// Users without email
$users = User::whereNull('email')->get();
// Users with email
$users = User::whereNotNull('email')->get();
```

---

### 47. (A) How do you use Laravel’s `auth` helper function?
**Answer:**
The `auth` helper provides access to Laravel's authentication services and the currently authenticated user.

**Example:**
```php
// Get authenticated user
$user = auth()->user();
// Check if user is authenticated
if (auth()->check()) {
    // User is logged in
}
```

---

### 48. (C) How do you implement Laravel’s custom session drivers?
**Answer:**
You can create custom session drivers by implementing the `SessionHandlerInterface` and registering it in the session configuration.

**Example:**
```php
// Custom session handler
class CustomSessionHandler implements SessionHandlerInterface {
    public function open($savePath, $sessionName) { /* implementation */ }
    public function close() { /* implementation */ }
    public function read($id) { /* implementation */ }
    public function write($id, $data) { /* implementation */ }
    public function destroy($id) { /* implementation */ }
    public function gc($maxlifetime) { /* implementation */ }
}
```

---

### 49. (B) How do you use Laravel’s `whereDate`, `whereMonth`, `whereYear` methods?
**Answer:**
These methods allow you to filter records based on specific date components.

**Example:**
```php
// Users created on specific date
$users = User::whereDate('created_at', '2023-12-25')->get();
// Users created in specific month
$users = User::whereMonth('created_at', 12)->get();
// Users created in specific year
$users = User::whereYear('created_at', 2023)->get();
```

---

### 50. (C) How do you implement Laravel’s custom queue drivers?
**Answer:**
You can create custom queue drivers by implementing the `Illuminate\Contracts\Queue\Queue` interface and registering it in the queue configuration.

**Example:**
```php
// Custom queue driver
class CustomQueueDriver implements Queue {
    public function push($job, $data = '', $queue = null) { /* implementation */ }
    public function later($delay, $job, $data = '', $queue = null) { /* implementation */ }
    public function pop($queue = null) { /* implementation */ }
    // ... other methods
}
```

---

### 51. (B) How do you use Laravel’s `whereRaw` and `orWhereRaw` methods?
**Answer:**
`whereRaw` allows you to write raw SQL where clauses, while `orWhereRaw` adds an OR condition with raw SQL.

**Example:**
```php
// Raw SQL where clause
$users = User::whereRaw('YEAR(created_at) = ?', [2023])->get();
// OR condition with raw SQL
$users = User::where('active', 1)->orWhereRaw('YEAR(created_at) = ?', [2023])->get();
```

---

### 52. (C) How do you implement Laravel’s custom mail drivers?
**Answer:**
You can create custom mail drivers by implementing the `Illuminate\Contracts\Mail\Mailer` interface and registering it in the mail configuration.

**Example:**
```php
// Custom mail driver
class CustomMailDriver implements Mailer {
    public function send($view, array $data, $callback) { /* implementation */ }
    public function raw($text, $callback) { /* implementation */ }
    public function html($html, $callback) { /* implementation */ }
    // ... other methods
}
```

---

### 53. (A) How do you use Laravel’s `route` helper function?
**Answer:**
The `route` helper generates URLs for named routes. You can pass parameters as an array.

**Example:**
```php
// Generate URL for named route
$url = route('users.show', ['user' => 1]);
// With query parameters
$url = route('users.index', ['page' => 2]);
```

---

### 54. (B) How do you use Laravel’s `whereExists` and `whereNotExists` methods?
**Answer:**
`whereExists` filters records based on the existence of a subquery, while `whereNotExists` filters based on the absence of a subquery.

**Example:**
```php
// Users who have posts
$users = User::whereExists(function ($query) {
    $query->select(DB::raw(1))->from('posts')->whereColumn('posts.user_id', 'users.id');
})->get();
```

---

### 55. (C) How do you implement Laravel’s custom validation rule objects?
**Answer:**
You can create custom validation rule objects by implementing the `Illuminate\Contracts\Validation\Rule` interface.

**Example:**
```php
class CustomRule implements Rule {
    public function passes($attribute, $value) {
        // Custom validation logic
        return true;
    }
    public function message() {
        return 'The :attribute is invalid.';
    }
}
// Usage
'field' => [new CustomRule],
```

---

### 56. (B) How do you use Laravel’s `whereColumn` method?
**Answer:**
`whereColumn` allows you to compare columns within the same table or across related tables.

**Example:**
```php
// Compare columns in same table
$users = User::whereColumn('created_at', 'updated_at')->get();
// Compare with different operator
$users = User::whereColumn('created_at', '>', 'updated_at')->get();
```

---

### 57. (A) How do you use Laravel’s `asset` helper function?
**Answer:**
The `asset` helper generates URLs for assets stored in the `public` directory.

**Example:**
```blade
<img src="{{ asset('images/logo.png') }}" alt="Logo">
<link href="{{ asset('css/app.css') }}" rel="stylesheet">
```

---

### 58. (C) How do you implement Laravel’s custom authentication guards?
**Answer:**
You can create custom authentication guards by implementing the `Illuminate\Contracts\Auth\Guard` interface and registering it in the auth configuration.

**Example:**
```php
// Custom guard
class CustomGuard implements Guard {
    public function user() { /* implementation */ }
    public function id() { /* implementation */ }
    public function validate(array $credentials = []) { /* implementation */ }
    public function hasUser() { /* implementation */ }
    public function setUser(Authenticatable $user) { /* implementation */ }
}
```

---

### 59. (B) How do you use Laravel’s `whereJsonContains` and `whereJsonLength` methods?
**Answer:**
These methods allow you to query JSON columns in the database.

**Example:**
```php
// Check if JSON array contains value
$users = User::whereJsonContains('options->languages', 'en')->get();
// Check JSON array length
$users = User::whereJsonLength('options->languages', '>', 2)->get();
```

---

### 60. (C) How do you implement Laravel’s custom notification channels?
**Answer:**
You can create custom notification channels by implementing the `Illuminate\Notifications\Notification` interface and defining the `via` method.

**Example:**
```php
// Custom notification channel
class CustomChannel {
    public function send($notifiable, $notification) {
        // Custom notification logic
    }
}
// In notification class
public function via($notifiable) {
    return [CustomChannel::class];
}
```

---

### 61. (B) How do you use Laravel’s `whereJsonPath` method?
**Answer:**
`whereJsonPath` allows you to query JSON columns using JSON path expressions.

**Example:**
```php
// Query JSON using path
$users = User::whereJsonPath('profile->address->city', 'New York')->get();
// With comparison operators
$users = User::whereJsonPath('profile->age', '>', 25)->get();
```

---

### 62. (C) How do you implement Laravel’s custom database query builders?
**Answer:**
You can create custom query builders by extending the `Illuminate\Database\Query\Builder` class and registering it in your model.

**Example:**
```php
// Custom query builder
class CustomQueryBuilder extends Builder {
    public function active() {
        return $this->where('active', 1);
    }
}
// In model
public function newQuery() {
    return new CustomQueryBuilder($this->getConnection(), $this->getConnection()->getQueryGrammar(), $this->getConnection()->getPostProcessor());
}
```

---

### 63. (A) How do you use Laravel’s `url` helper function?
**Answer:**
The `url` helper generates URLs for your application. It can be used to generate absolute URLs.

**Example:**
```php
// Generate absolute URL
$url = url('/users');
// With parameters
$url = url('/users', ['id' => 1]);
```

---

### 64. (B) How do you use Laravel’s `whereTime`, `whereDate`, `whereDay` methods?
**Answer:**
These methods allow you to filter records based on specific time components.

**Example:**
```php
// Filter by time
$users = User::whereTime('created_at', '09:00:00')->get();
// Filter by day of month
$users = User::whereDay('created_at', 15)->get();
```

---

### 65. (C) How do you implement Laravel’s custom encryption drivers?
**Answer:**
You can create custom encryption drivers by implementing the `Illuminate\Contracts\Encryption\Encrypter` interface and registering it in the encryption configuration.

**Example:**
```php
// Custom encryption driver
class CustomEncrypter implements Encrypter {
    public function encrypt($value, $serialize = true) { /* implementation */ }
    public function decrypt($payload, $unserialize = true) { /* implementation */ }
}
```

---

### 66. (B) How do you use Laravel’s `whereRegexp` method?
**Answer:**
`whereRegexp` allows you to filter records using regular expressions (MySQL only).

**Example:**
```php
// Filter using regex
$users = User::whereRegexp('email', '^[a-z]+@example\.com$')->get();
```

---

### 67. (A) How do you use Laravel’s `bcrypt` helper function?
**Answer:**
The `bcrypt` helper hashes a value using the Bcrypt algorithm, commonly used for password hashing.

**Example:**
```php
// Hash password
$hashedPassword = bcrypt('password123');
// With custom rounds
$hashedPassword = bcrypt('password123', ['rounds' => 12]);
```

---

### 68. (C) How do you implement Laravel’s custom file system drivers?
**Answer:**
You can create custom file system drivers by implementing the `Illuminate\Contracts\Filesystem\Filesystem` interface and registering it in the filesystem configuration.

**Example:**
```php
// Custom file system driver
class CustomFilesystemDriver implements Filesystem {
    public function exists($path) { /* implementation */ }
    public function get($path) { /* implementation */ }
    public function put($path, $contents, $options = []) { /* implementation */ }
    // ... other methods
}
```

---

### 69. (B) How do you use Laravel’s `whereFullText` method?
**Answer:**
`whereFullText` allows you to perform full-text searches on columns that have full-text indexes (MySQL only).

**Example:**
```php
// Full-text search
$posts = Post::whereFullText(['title', 'content'], 'search term')->get();
```

---

### 70. (C) How do you implement Laravel’s custom log drivers?
**Answer:**
You can create custom log drivers by implementing the `Psr\Log\LoggerInterface` and registering it in the logging configuration.

**Example:**
```php
// Custom log driver
class CustomLogDriver implements LoggerInterface {
    public function emergency($message, array $context = []) { /* implementation */ }
    public function alert($message, array $context = []) { /* implementation */ }
    public function critical($message, array $context = []) { /* implementation */ }
    public function error($message, array $context = []) { /* implementation */ }
    public function warning($message, array $context = []) { /* implementation */ }
    public function notice($message, array $context = []) { /* implementation */ }
    public function info($message, array $context = []) { /* implementation */ }
    public function debug($message, array $context = []) { /* implementation */ }
    public function log($level, $message, array $context = []) { /* implementation */ }
}
```

---

### 71. (B) How do you use Laravel’s `whereRowValues` method?
**Answer:**
`whereRowValues` allows you to compare multiple columns with multiple values in a single where clause.

**Example:**
```php
// Compare multiple columns
$users = User::whereRowValues(['first_name', 'last_name'], '=', ['John', 'Doe'])->get();
```

---

### 72. (C) How do you implement Laravel’s custom view composers?
**Answer:**
View composers allow you to share data with all views or specific views. You can register them in a service provider.

**Example:**
```php
// In AppServiceProvider
View::composer('*', function ($view) {
    $view->with('userCount', User::count());
});
// Or for specific view
View::composer('users.index', function ($view) {
    $view->with('users', User::all());
});
```

---

### 73. (A) How do you use Laravel’s `str_random` helper function?
**Answer:**
The `str_random` helper generates a random string of the specified length.

**Example:**
```php
// Generate random string
$randomString = str_random(16);
// Common use case for tokens
$token = str_random(60);
```

---

### 74. (B) How do you use Laravel’s `whereJsonSearch` method?
**Answer:**
`whereJsonSearch` allows you to search within JSON arrays using the `JSON_SEARCH` function (MySQL 5.7+).

**Example:**
```php
// Search in JSON array
$users = User::whereJsonSearch('tags', 'one')->get();
// With path
$users = User::whereJsonSearch('profile->interests', 'coding')->get();
```

---

### 75. (C) How do you implement Laravel’s custom route model binding?
**Answer:**
You can customize route model binding by overriding the `getRouteKeyName` method in your model or using the `RouteServiceProvider`.

**Example:**
```php
// In model
public function getRouteKeyName() {
    return 'slug';
}
// Or in RouteServiceProvider
Route::bind('user', function ($value) {
    return User::where('username', $value)->firstOrFail();
});
```

---

### 76. (B) How do you use Laravel’s `whereStartsWith` and `whereEndsWith` methods?
**Answer:**
These methods allow you to filter records where a column value starts or ends with a specific string.

**Example:**
```php
// Users with email starting with 'admin'
$users = User::whereStartsWith('email', 'admin')->get();
// Users with email ending with '.com'
$users = User::whereEndsWith('email', '.com')->get();
```

---

### 77. (A) How do you use Laravel’s `csrf_token` helper function?
**Answer:**
The `csrf_token` helper retrieves the current CSRF token value.

**Example:**
```blade
<meta name="csrf-token" content="{{ csrf_token() }}">
<input type="hidden" name="_token" value="{{ csrf_token() }}">
```

---

### 78. (C) How do you implement Laravel’s custom database connection resolvers?
**Answer:**
You can create custom database connection resolvers by implementing the `Illuminate\Database\Connectors\ConnectorInterface` and registering it in the database configuration.

**Example:**
```php
// Custom connector
class CustomConnector implements ConnectorInterface {
    public function connect(array $config) {
        // Custom connection logic
    }
}
```

---

### 79. (B) How do you use Laravel’s `whereLike` and `whereNotLike` methods?
**Answer:**
These methods allow you to filter records using SQL LIKE patterns.

**Example:**
```php
// Users with name containing 'john'
$users = User::whereLike('name', '%john%')->get();
// Users with name not containing 'admin'
$users = User::whereNotLike('name', '%admin%')->get();
```

---

### 80. (C) How do you implement Laravel’s custom response macros?
**Answer:**
You can create custom response macros using the `Response::macro` method, typically in a service provider.

**Example:**
```php
// In AppServiceProvider
Response::macro('api', function ($data, $message = '', $status = 200) {
    return response()->json([
        'data' => $data,
        'message' => $message,
        'status' => $status
    ], $status);
});
// Usage
return response()->api($users, 'Users retrieved successfully');
```

---

### 81. (B) How do you use Laravel’s `whereBetweenColumns` method?
**Answer:**
`whereBetweenColumns` allows you to filter records where a column value is between two other column values.

**Example:**
```php
// Users where created_at is between updated_at and deleted_at
$users = User::whereBetweenColumns('created_at', ['updated_at', 'deleted_at'])->get();
```

---

### 82. (C) How do you implement Laravel’s custom validation rule objects with parameters?
**Answer:**
You can create custom validation rules that accept parameters by implementing the `Illuminate\Contracts\Validation\Rule` interface and accepting parameters in the constructor.

**Example:**
```php
class CustomRule implements Rule {
    private $parameter;
    
    public function __construct($parameter) {
        $this->parameter = $parameter;
    }
    
    public function passes($attribute, $value) {
        // Use $this->parameter in validation logic
        return true;
    }
    
    public function message() {
        return 'The :attribute is invalid.';
    }
}
// Usage
'field' => [new CustomRule('parameter')],
```

---

### 83. (A) How do you use Laravel’s `now` helper function?
**Answer:**
The `now` helper creates a new `Carbon` instance for the current date and time.

**Example:**
```php
// Get current date and time
$currentTime = now();
// Add time
$futureTime = now()->addDays(7);
```

---

### 84. (B) How do you use Laravel’s `whereJsonLength` method with comparison operators?
**Answer:**
`whereJsonLength` allows you to filter records based on the length of JSON arrays with various comparison operators.

**Example:**
```php
// Users with more than 2 tags
$users = User::whereJsonLength('tags', '>', 2)->get();
// Users with exactly 1 role
$users = User::whereJsonLength('roles', '=', 1)->get();
```

---

### 85. (C) How do you implement Laravel’s custom database query grammar?
**Answer:**
You can create custom query grammars by extending the `Illuminate\Database\Query\Grammars\Grammar` class and implementing custom SQL generation logic.

**Example:**
```php
// Custom query grammar
class CustomQueryGrammar extends Grammar {
    public function compileSelect(Builder $query) {
        // Custom SELECT compilation logic
        return parent::compileSelect($query);
    }
}
```

---

### 86. (B) How do you use Laravel’s `whereRaw` with multiple parameters?
**Answer:**
`whereRaw` can accept multiple parameters for prepared statements to prevent SQL injection.

**Example:**
```php
// Multiple parameters
$users = User::whereRaw('YEAR(created_at) = ? AND MONTH(created_at) = ?', [2023, 12])->get();
// With named parameters
$users = User::whereRaw('created_at BETWEEN ? AND ?', ['2023-01-01', '2023-12-31'])->get();
```

---

### 87. (A) How do you use Laravel’s `optional` helper function?
**Answer:**
The `optional` helper allows you to access properties or call methods on an object that may be null without throwing an error.

**Example:**
```php
// Safe property access
$userName = optional($user)->name;
// Safe method call
$postCount = optional($user)->posts()->count();
```

---

### 88. (C) How do you implement Laravel’s custom cache tags?
**Answer:**
You can create custom cache tags by implementing the `Illuminate\Contracts\Cache\Store` interface and adding tag support.

**Example:**
```php
// Custom cache store with tags
class CustomCacheStore implements Store {
    public function tags($names) {
        return new CustomTaggedCache($this, is_array($names) ? $names : func_get_args());
    }
    // ... other methods
}
```

---

### 89. (B) How do you use Laravel’s `whereJsonContains` with multiple values?
**Answer:**
`whereJsonContains` can be used to check if a JSON array contains multiple values.

**Example:**
```php
// Check if JSON array contains multiple values
$users = User::whereJsonContains('tags', ['php', 'laravel'])->get();
// Using orWhereJsonContains
$users = User::whereJsonContains('tags', 'php')
    ->orWhereJsonContains('tags', 'laravel')
    ->get();
```

---

### 90. (C) How do you implement Laravel’s custom database connection pooling?
**Answer:**
You can implement custom database connection pooling by extending the database connection class and managing connection pools.

**Example:**
```php
// Custom connection with pooling
class PooledDatabaseConnection extends Connection {
    private $pool = [];
    private $maxConnections = 10;
    
    public function getPdo() {
        if (empty($this->pool)) {
            return parent::getPdo();
        }
        return array_pop($this->pool);
    }
    
    public function returnToPool($pdo) {
        if (count($this->pool) < $this->maxConnections) {
            $this->pool[] = $pdo;
        }
    }
}
```

---

### 91. (B) How do you use Laravel’s `whereJsonPath` with array indexing?
**Answer:**
`whereJsonPath` can be used with array indexing to query specific elements in JSON arrays.

**Example:**
```php
// Query first element in JSON array
$users = User::whereJsonPath('tags[0]', 'php')->get();
// Query specific nested array element
$users = User::whereJsonPath('profile->addresses[0]->city', 'New York')->get();
```

---

### 92. (C) How do you implement Laravel’s custom database query post-processors?
**Answer:**
You can create custom query post-processors by extending the `Illuminate\Database\Query\Processors\Processor` class to modify query results.

**Example:**
```php
// Custom query processor
class CustomQueryProcessor extends Processor {
    public function processSelect(Builder $query, $results) {
        // Custom processing logic
        return parent::processSelect($query, $results);
    }
}
```

---

### 93. (A) How do you use Laravel’s `data_get` helper function?
**Answer:**
The `data_get` helper retrieves a value from a nested array or object using "dot" notation, with a default value if the key doesn't exist.

**Example:**
```php
// Get nested value
$city = data_get($user, 'profile.address.city', 'Unknown');
// Get array element
$firstTag = data_get($user, 'tags.0', 'default');
```

---

### 94. (B) How do you use Laravel’s `whereJsonSearch` with different search modes?
**Answer:**
`whereJsonSearch` supports different search modes like 'one', 'all', or 'none' to control how multiple search terms are handled.

**Example:**
```php
// Search for any of the terms (one)
$users = User::whereJsonSearch('tags', 'one', ['php', 'laravel'])->get();
// Search for all terms (all)
$users = User::whereJsonSearch('tags', 'all', ['php', 'laravel'])->get();
```

---

### 95. (C) How do you implement Laravel’s custom database schema builders?
**Answer:**
You can create custom schema builders by extending the `Illuminate\Database\Schema\Builder` class to add custom schema operations.

**Example:**
```php
// Custom schema builder
class CustomSchemaBuilder extends Builder {
    public function createCustomTable($table, $callback) {
        // Custom table creation logic
        return $this->createTable($table, $callback);
    }
}
```

---

### 96. (B) How do you use Laravel’s `whereRaw` with subqueries?
**Answer:**
`whereRaw` can be used with subqueries to create complex filtering conditions.

**Example:**
```php
// Subquery in whereRaw
$users = User::whereRaw('id IN (SELECT user_id FROM posts WHERE active = 1)')->get();
// Complex subquery
$users = User::whereRaw('(SELECT COUNT(*) FROM posts WHERE posts.user_id = users.id) > 5')->get();
```

---

### 97. (A) How do you use Laravel’s `collect` helper function?
**Answer:**
The `collect` helper creates a new collection instance from the given array or object.

**Example:**
```php
// Create collection from array
$collection = collect([1, 2, 3, 4, 5]);
// Create collection from object
$collection = collect($user);
// Chain methods
$filtered = collect($users)->filter(fn($user) => $user->active)->pluck('name');
```

---

### 98. (C) How do you implement Laravel’s custom database connection factories?
**Answer:**
You can create custom database connection factories by implementing the `Illuminate\Database\Connectors\ConnectorInterface` and custom connection logic.

**Example:**
```php
// Custom connection factory
class CustomConnectionFactory {
    public function createConnection(array $config) {
        // Custom connection creation logic
        return new CustomDatabaseConnection($config);
    }
}
```

---

### 99. (B) How do you use Laravel’s `whereJsonPath` with comparison operators and functions?
**Answer:**
`whereJsonPath` can be combined with SQL functions and comparison operators for complex JSON queries.

**Example:**
```php
// Using JSON functions
$users = User::whereJsonPath('JSON_LENGTH(profile->tags)', '>', 3)->get();
// Complex JSON path with comparison
$users = User::whereJsonPath('profile->age', '>', 25)->get();
```

---

### 100. (C) How do you implement Laravel’s custom database query expression builders?
**Answer:**
You can create custom query expression builders by extending the `Illuminate\Database\Query\Expression` class to add custom SQL expressions.

**Example:**
```php
// Custom query expression
class CustomExpression extends Expression {
    public function getValue(Connection $connection) {
        // Custom expression logic
        return 'CUSTOM_SQL_FUNCTION()';
    }
}
// Usage
$users = User::whereRaw(new CustomExpression())->get();
```

---

## 🎯 **Summary**

This comprehensive collection of 100 senior Laravel questions covers:

- **Basic Concepts (A)**: Helper functions, simple relationships, basic validation
- **Intermediate Topics (B)**: Advanced querying, middleware, events, caching
- **Advanced Concepts (C)**: Custom implementations, complex patterns, system architecture

Each question includes:
- ✅ Difficulty level (A/B/C)
- ✅ Detailed explanation
- ✅ Practical code examples
- ✅ Real-world usage scenarios

Perfect for:
- 🎓 Senior Laravel developer interviews
- 📚 Self-assessment and learning
- 🏢 Technical team evaluations
- 📖 Laravel certification preparation

---

*Generated with comprehensive Laravel knowledge and practical examples for senior-level understanding.*


