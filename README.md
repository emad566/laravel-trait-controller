# Laravel Trait Controller

A Laravel package that provides reusable controller traits for common CRUD operations with advanced filtering, soft deletes, security features, and validation. This package eliminates repetitive controller code and provides a consistent API structure across your Laravel applications.

## Features

- 🚀 **Enhanced CRUD operations** - ListingTrait, RetrievalTrait, EditFormTrait, DeletionTrait, and StatusToggleTrait
- 🔍 **Advanced filtering** - Multi-column sorting, range filtering, relationship filtering, and include options
- 📄 **Smart pagination** - Configurable pagination with metadata and validation
- 🗑️ **Intelligent soft delete support** - Seamlessly handles models with or without soft deletes
- ✅ **Comprehensive validation** - Built-in security validation with XSS and SQL injection prevention
- 🎯 **Flexible callback system** - Hooks for custom business logic at any stage
- ⚡ **Performance optimized** - Query builder macros, eager loading, and advanced caching
- 🛡️ **Security focused** - Input sanitization, validation, and audit logging
- 🎛️ **Highly configurable** - Extensive configuration with environment-based settings
- 📊 **Rich API responses** - Standardized JSON responses with metadata and debugging info
- 📝 **Audit logging** - Built-in logging for operations and errors

## Installation

Install the package via Composer:

```bash
composer require emadsoliman/laravel-trait-controller
```

### Laravel Auto-Discovery

The package will automatically register its service provider and publish the configuration file in Laravel 5.5+.

For older versions, manually add the service provider to your `config/app.php`:

```php
'providers' => [
    // Other service providers...
    EmadSoliman\LaravelTraitController\LaravelTraitControllerServiceProvider::class,
],
```

### Publish Configuration

Publish the configuration file to customize the package behavior:

```bash
php artisan vendor:publish --tag=trait-controller-config
```

## Quick Start

### 1. Create a Base Controller

```php
<?php

namespace App\Http\Controllers;

use EmadSoliman\LaravelTraitController\Controllers\BaseController;
use EmadSoliman\LaravelTraitController\Traits\ListingTrait;
use EmadSoliman\LaravelTraitController\Traits\RetrievalTrait;
use EmadSoliman\LaravelTraitController\Traits\EditFormTrait;
use EmadSoliman\LaravelTraitController\Traits\DeletionTrait;
use EmadSoliman\LaravelTraitController\Traits\StatusToggleTrait;

class ProductController extends BaseController
{
    use ListingTrait, RetrievalTrait, EditFormTrait, DeletionTrait, StatusToggleTrait;

    public function __construct()
    {
        parent::__construct(\App\Models\Product::class, ['internal_notes']);
    }

    public function index(Request $request)
    {
        return $this->listingInit($request);
    }

    public function show($id)
    {
        return $this->retrievalInit($id);
    }

    public function edit($id)
    {
        return $this->editFormInit($id);
    }

    public function destroy($id)
    {
        return $this->deletionInit($id);
    }

    public function toggleStatus($id, $state)
    {
        return $this->statusToggleInit($id, $state);
    }
}
```

### 2. Advanced Usage with Callbacks

```php
public function index(Request $request)
{
    return $this->listingInit(
        $request,
        // Before filtering callback
        function ($query) use ($request) {
            if ($request->category_id) {
                $query->where('category_id', $request->category_id);
            }
            return [$query];
        },
        // Additional validations
        ['category_id' => 'nullable|exists:categories,id'],
        // Include soft deleted records
        true,
        // After data retrieval callback
        function ($items) {
            // Custom processing after data is retrieved
            return [$items];
        },
        // Helper data
        ['categories' => Category::all()],
        // Eager load relationships
        ['category', 'tags'],
        // Load additional relationships after pagination
        ['reviews'],
        // Enable global search
        true,
        // Timestamp column for date filtering
        'created_at',
        // Include options (like Laravel API resources)
        [
            'reviews' => [
                'with' => 'reviews.user',
                'callback' => function ($query, $request) {
                    return $query->whereHas('reviews', function ($q) {
                        $q->where('approved', true);
                    });
                }
            ]
        ]
    );
}
```

## Available Traits

### IndexTrait

Provides listing/pagination functionality with advanced filtering.

**Parameters:**
- `$request` - The HTTP request
- `$callback` - Optional callback for custom query modifications
- `$validations` - Additional validation rules
- `$includeTrashed` - Include soft deleted records
- `$afterGet` - Callback after data retrieval
- `$helpers` - Additional data for response
- `$with` - Relationships to eager load
- `$load` - Relationships to load after pagination
- `$enableSearch` - Enable/disable search functionality
- `$createdAtColumn` - Column name for date filtering

**Features:**
- Automatic pagination
- Column-based filtering
- Date range filtering
- Search functionality across all fillable columns
- Soft delete support
- Custom sorting

### ShowTrait

Retrieve a single record with optional relationships and custom processing.

```php
public function show($id)
{
    return $this->showInit($id, function ($item) {
        $item->load(['category', 'tags']);
        return [$item];
    });
}
```

### EditTrait

Retrieve data for editing, including the record and any necessary form data.

```php
public function edit($id)
{
    return $this->editInit($id, function ($item) {
        $item->load(['category']);
        return [$item];
    });
}
```

### DestroyTrait

Delete records with support for both soft deletes and force deletion.

```php
public function destroy($id)
{
    return $this->destroyInit($id, function ($item) {
        // Custom logic before deletion
        if ($item->orders_count > 0) {
            return [false, $this->sendResponse(false, [], 'Cannot delete product with orders')];
        }
        return [$item];
    });
}
```

### ToggleActiveTrait

Toggle active/inactive status of records using soft deletes or status columns.

```php
public function toggleActive($id, $state)
{
    return $this->toggleActiveInit($id, $state);
}
```

## Configuration

The package comes with a comprehensive configuration file:

```php
return [
    'pagination' => [
        'per_page' => 15,
        'max_per_page' => 100,
        'max_page' => 1000,
    ],
    
    'list_validations' => [
        'per_page' => 'nullable|numeric|min:1|max:100',
        'page' => 'nullable|numeric|min:1|max:1000',
        'sort_direction' => 'nullable|in:ASC,DESC',
        'date_from' => 'nullable|date',
        'date_to' => 'nullable|date',
        'q' => 'nullable|string|max:255',
    ],
    
    'cache' => [
        'enabled' => false,
        'ttl' => 3600,
        'prefix' => 'trait_controller',
    ],
    
    'soft_deletes' => [
        'force_delete_by_default' => false,
        'include_trashed_by_default' => false,
    ],
];
```

## Query Builder Macros

The package automatically registers helpful query builder macros:

```php
// Use anywhere in your queries
User::like('name', 'john')->get();
Product::likeStart('sku', 'ABC')->get();
Post::orLike('title', 'laravel')->get();
```

## API Response Structure

All traits return consistent JSON responses:

```json
{
    "status": true,
    "message": "Success message",
    "data": {
        "items": {
            "data": [...],
            "current_page": 1,
            "per_page": 15,
            "total": 100
        },
        "helpers": {...}
    },
    "errors": null
}
```

## Error Handling

The package includes comprehensive error handling:

```json
{
    "status": false,
    "message": "Validation failed",
    "data": null,
    "errors": {
        "field_name": ["Error message"]
    }
}
```

## Request Classes

### BaseFormRequest

The package includes a comprehensive `BaseFormRequest` class with built-in security features:

- **XSS Prevention**: Automatic sanitization of HTML content
- **SQL Injection Protection**: Custom validation rules to prevent SQL injection
- **Path Traversal Prevention**: Protection against directory traversal attacks
- **Input Sanitization**: Automatic trimming and cleaning of inputs
- **Enhanced Error Responses**: Structured error responses with input masking for sensitive data

### FilterRequest

The `FilterRequest` extends `BaseFormRequest` with comprehensive filtering capabilities for your listing endpoints:

```php
use EmadSoliman\LaravelTraitController\Http\Requests\FilterRequest;

public function index(FilterRequest $request)
{
    return $this->listingInit($request);
}
```

**Features:**
- **Pagination**: `page`, `per_page` with limits
- **Sorting**: Single or multi-column sorting with `sortColumn`, `sortDirection`, `sort_columns`, `sort_directions`
- **Date Filtering**: Date ranges, predefined periods (`created_today`, `created_this_week`, etc.)
- **Text Search**: Global search with `q`, specific field search with `name`, `search_columns`
- **Advanced Filtering**: Custom filters with `filters`, `ranges`, `relationships`
- **Include Options**: Laravel API resource-style includes with `include`
- **Category/Product/User Filtering**: Pre-built filtering for common entities
- **Price Filtering**: Range filtering with `min_price`, `max_price`, `price_range`
- **Security**: All inputs are validated and sanitized automatically

**Usage Example:**
```php
// GET /products?page=1&per_page=20&q=laptop&category_ids[]=1&category_ids[]=2
// &min_price=100&max_price=500&sort_columns[]=name&sort_directions[]=ASC
// &created_today=true&include=category,reviews
```

## Helper Functions

The package provides several helper functions you can override in your application:

```php
// Override these functions in your app to customize behavior
function should_include_trashed(): bool
{
    return auth('admin')->check();
}

function should_force_delete(): bool
{
    return auth('admin')->check() && request()->has('force');
}
```

## Customization

### Custom Base Controller

Extend the base controller to add your own methods:

```php
<?php

namespace App\Http\Controllers;

use EmadSoliman\LaravelTraitController\Controllers\BaseController as TraitBaseController;

class BaseController extends TraitBaseController
{
    public function __construct(?string $model = null, array $excludedColumns = [])
    {
        parent::__construct($model, $excludedColumns);
        
        // Your custom logic
    }
    
    // Override methods as needed
    public function sendResponse($status = true, $data = null, $message = '', $errors = null, $code = 200, $request = null)
    {
        // Custom response formatting
        return parent::sendResponse($status, $data, $message, $errors, $code, $request);
    }
}
```

### Model Requirements

Your models should follow Laravel conventions:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes; // Optional

    protected $fillable = [
        'name',
        'description',
        'price',
        'category_id',
    ];

    // Define relationships
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
```

### Resource Classes

Create API resources for consistent data formatting:

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
```

## Testing

```bash
vendor/bin/phpunit
```

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security

If you discover any security-related issues, please email emadsoliman@example.com instead of using the issue tracker.

## Credits

- [Emad Soliman](https://github.com/emadsoliman)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Laravel Version Compatibility

| Laravel | Package |
|---------|---------|
| 10.x    | 1.x     |
| 11.x    | 1.x     |

## Support

If you find this package useful, please consider giving it a ⭐ on GitHub!

For support, please open an issue on GitHub or contact emadsoliman@example.com. 
