# Laravel Trait Controller

A Laravel package that provides reusable controller traits for common CRUD operations with advanced filtering, soft deletes, caching support, and validation. This package eliminates repetitive controller code and provides a consistent API structure across your Laravel applications.

## Features

- 🚀 **Ready-to-use CRUD operations** - Index, Show, Edit, Destroy, and Toggle Active
- 🔍 **Advanced filtering** - Column-based filtering, date ranges, and search functionality  
- 📄 **Automatic pagination** - Configurable pagination with validation
- 🗑️ **Soft delete support** - Seamlessly handles models with or without soft deletes
- ✅ **Built-in validation** - Automatic validation with customizable rules
- 🎯 **Callback support** - Hooks for custom business logic at any stage
- ⚡ **Performance optimized** - Query builder macros and eager loading support
- 🎛️ **Highly configurable** - Extensive configuration options
- 📊 **Consistent responses** - Standardized JSON API responses

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
use EmadSoliman\LaravelTraitController\Traits\IndexTrait;
use EmadSoliman\LaravelTraitController\Traits\ShowTrait;
use EmadSoliman\LaravelTraitController\Traits\EditTrait;
use EmadSoliman\LaravelTraitController\Traits\DestroyTrait;
use EmadSoliman\LaravelTraitController\Traits\ToggleActiveTrait;

class ProductController extends BaseController
{
    use IndexTrait, ShowTrait, EditTrait, DestroyTrait, ToggleActiveTrait;

    public function __construct()
    {
        parent::__construct(\App\Models\Product::class, ['internal_notes']);
    }

    public function index(Request $request)
    {
        return $this->indexInit($request);
    }

    public function show($id)
    {
        return $this->showInit($id);
    }

    public function edit($id)
    {
        return $this->editInit($id);
    }

    public function destroy($id)
    {
        return $this->destroyInit($id);
    }

    public function toggleActive($id, $state)
    {
        return $this->toggleActiveInit($id, $state);
    }
}
```

### 2. Advanced Usage with Callbacks

```php
public function index(Request $request)
{
    return $this->indexInit(
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
        ['reviews']
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
