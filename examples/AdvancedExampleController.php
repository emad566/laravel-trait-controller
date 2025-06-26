<?php

/**
 * EXAMPLE CONTROLLER - FOR REFERENCE ONLY
 *
 * This is an example controller demonstrating how to use the Laravel Trait Controller package.
 * Copy this file to your Laravel application and modify it according to your needs.
 *
 * Note: This file references models (Product, Category, Brand, etc.) that don't exist in the package.
 * You should replace these with your actual model references when implementing in your application.
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use EmadSoliman\LaravelTraitController\Controllers\BaseController;
use EmadSoliman\LaravelTraitController\Traits\ListingTrait;
use EmadSoliman\LaravelTraitController\Traits\RetrievalTrait;
use EmadSoliman\LaravelTraitController\Traits\EditFormTrait;
use EmadSoliman\LaravelTraitController\Traits\DeletionTrait;
use EmadSoliman\LaravelTraitController\Traits\StatusToggleTrait;
use EmadSoliman\LaravelTraitController\Http\Requests\BaseFormRequest;
use EmadSoliman\LaravelTraitController\Http\Requests\FilterRequest;
use EmadSoliman\LaravelTraitController\Helpers\CustomLogger;

// NOTE: Replace with your actual model imports
// use App\Models\Product;
// use App\Models\Category;
// use App\Models\Brand;
// use App\Models\Tag;

/**
 * Advanced Product Controller demonstrating complex features
 * of the Laravel Trait Controller package (FOR REFERENCE ONLY)
 *
 * This example includes business logic, relationships, and advanced filtering.
 * Copy and modify according to your application's needs.
 */
class AdvancedExampleController extends BaseController
{
    use ListingTrait, RetrievalTrait, EditFormTrait, DeletionTrait, StatusToggleTrait;

    public function __construct()
    {
        // Auto-configure the controller for your model
        // Replace 'Product::class' with your actual model class
        // Exclude sensitive fields from filtering
        // parent::__construct(Product::class, ['internal_notes', 'cost_price']);

        // Example for when you implement this in your application:
        // parent::__construct(\App\Models\Product::class, ['internal_notes', 'cost_price']);
    }

    /**
     * Advanced product listing with comprehensive FilterRequest validation
     */
    public function indexWithFilterRequest(FilterRequest $request)
    {
        return $this->listingInit(
            $request,
            // FilterRequest automatically handles validation, just add business logic
            function ($query) use ($request) {
                CustomLogger::info('product-filters.log', 'Using FilterRequest for advanced filtering', [
                    'filters_applied' => 'FilterRequest handles comprehensive validation',
                    'timestamp' => date('Y-m-d H:i:s'),
                ]);

                // Business-specific logic can be added here
                // FilterRequest already handles most common filtering patterns

                return [$query];
            },
            // No additional validations needed - FilterRequest handles comprehensive validation
            [],
            // Include soft deleted based on permissions
            should_include_trashed(),
            // After retrieval callback
            function ($items) {
                CustomLogger::info('product-results.log', 'Products retrieved with FilterRequest', [
                    'count' => $items->count(),
                    'total' => $items->total(),
                ]);
                return [$items];
            },
            // Helper data
            [
                'filter_info' => 'Using FilterRequest for comprehensive validation and filtering'
            ],
            // Eager load relationships
            ['category', 'brand'],
            // Load after pagination
            ['reviews'],
            // Enable global search
            true,
            // Custom timestamp column
            'created_at'
        );
    }

    /**
     * Advanced product listing with multiple filtering options
     */
    public function index(BaseFormRequest $request)
    {
        return $this->listingInit(
            $request,
            // Before filter callback - custom query modifications
            function ($query) use ($request) {
                // Category filtering
                if ($request->category_id) {
                    $query->where('category_id', $request->category_id);
                }

                // Advanced price filtering with ranges
                if ($request->price_range) {
                    $range = $request->price_range;
                    if (isset($range['min'])) {
                        $query->where('price', '>=', $range['min']);
                    }
                    if (isset($range['max'])) {
                        $query->where('price', '<=', $range['max']);
                    }
                }

                return [$query];
            },
            // Additional validation rules
            [
                'category_id' => 'nullable|exists:categories,id',
                'price_range' => 'nullable|array',
                'price_range.min' => 'nullable|numeric|min:0',
                'price_range.max' => 'nullable|numeric|min:0',
                'stock_status' => 'nullable|in:in_stock,low_stock,out_of_stock',
                'brand_id' => 'nullable|exists:brands,id',
            ],
            // Include soft deleted records based on user permissions
            should_include_trashed(),
            // After retrieval callback - post-processing
            function ($items) {
                // Add computed fields or modify collection
                return [$items];
            },
            // Helper data for frontend (replace with your actual models)
            [
                // 'categories' => Category::all(), // Uncomment and use your models
                // 'brands' => Brand::all(),       // Uncomment and use your models
                'price_ranges' => [
                    ['min' => 0, 'max' => 50, 'label' => 'Under $50'],
                    ['min' => 50, 'max' => 100, 'label' => '$50 - $100'],
                    ['min' => 100, 'max' => 500, 'label' => '$100 - $500'],
                    ['min' => 500, 'max' => null, 'label' => 'Over $500'],
                ]
            ],
            // Eager load relationships
            ['category', 'brand', 'tags'],
            // Load after pagination for performance
            ['reviews.user'],
            // Enable global search
            true,
            // Custom timestamp column
            'created_at',
            // Include options - similar to Laravel API resources
            $this->getAvailableIncludes()
        );
    }

    /**
     * Show single product with relationships
     */
    public function show($id)
    {
        return $this->retrievalInit($id, function ($item) {
            // Load all necessary relationships for detailed view
            $item->load([
                'category',
                'brand',
                'tags',
                'reviews.user',
                'variations',
                'media'
            ]);

            // Add computed fields
            $item->average_rating = $item->reviews->avg('rating');
            $item->reviews_count = $item->reviews->count();

            return [$item];
        });
    }

    /**
     * Get product edit form data
     */
    public function edit($id)
    {
        return $this->editFormInit($id, function ($item) {
            // Load relationships needed for editing
            $item->load(['category', 'brand', 'tags', 'variations']);
            return [$item];
        });
    }

    /**
     * Create form data
     */
    public function create()
    {
        return $this->sendResponse(true, [
            // 'categories' => Category::where('active', true)->get(), // Uncomment and use your models
            // 'brands' => Brand::where('active', true)->get(),       // Uncomment and use your models
            // 'tags' => Tag::all(),                                  // Uncomment and use your models
            // 'tax_rates' => config('shop.tax_rates'),              // Configure your tax rates
        ], 'Create form data retrieved');
    }

    /**
     * Store new product
     */
    public function store(BaseFormRequest $request)
    {
        // Example implementation - replace with your actual model logic
        /*
        try {
            $product = Product::create($request->validated());

            // Handle relationships
            if ($request->tag_ids) {
                $product->tags()->attach($request->tag_ids);
            }

            return $this->sendResponse(true, [
                'item' => new \App\Http\Resources\ProductResource($product->load('category', 'brand'))
            ], 'Product created successfully', null, 201);

        } catch (\Throwable $th) {
            return $this->sendServerError('Error creating product', null, $th);
        }
        */

        return $this->sendResponse(true, [], 'Example store method - implement with your model');
    }

    /**
     * Update product
     */
    public function update(BaseFormRequest $request, $id)
    {
        // Example implementation - replace with your actual model logic
        /*
        try {
            $product = Product::findOrFail($id);
            $product->update($request->validated());

            // Handle relationships
            if ($request->has('tag_ids')) {
                $product->tags()->sync($request->tag_ids);
            }

            return $this->sendResponse(true, [
                'item' => new \App\Http\Resources\ProductResource($product->refresh()->load('category', 'brand'))
            ], 'Product updated successfully');

        } catch (\Throwable $th) {
            return $this->sendServerError('Error updating product', null, $th);
        }
        */

        return $this->sendResponse(true, [], 'Example update method - implement with your model');
    }

    /**
     * Delete product with business logic validation
     */
    public function destroy($id)
    {
        return $this->deletionInit($id, function ($item) {
            // Business logic validation before deletion
            if ($item->orders()->count() > 0) {
                return [false, $this->sendResponse(false, [], 'Cannot delete product with existing orders', null, 422)];
            }

            if ($item->variations()->count() > 0) {
                return [false, $this->sendResponse(false, [], 'Please delete product variations first', null, 422)];
            }

            // Clean up related data
            $item->tags()->detach();
            $item->media()->delete();

            return [$item];
        });
    }

    /**
     * Toggle product status with business validation
     */
    public function toggleStatus($id, $state)
    {
        return $this->statusToggleInit($id, $state, function ($item) {
            // Business logic validation
            if ($item->stock < 1 && $state === 'true') {
                return [false, $this->sendResponse(false, [], 'Cannot activate product with no stock', null, 422)];
            }

            if (!$item->category->active && $state === 'true') {
                return [false, $this->sendResponse(false, [], 'Cannot activate product in inactive category', null, 422)];
            }

            return [$item];
        });
    }

    /**
     * Get available include options for API resources
     */
    protected function getAvailableIncludes(): array
    {
        return [
            'category' => [
                'with' => 'category',
                'callback' => function ($query, $request) {
                    return $query;
                }
            ],
            'brand' => [
                'with' => 'brand',
                'callback' => function ($query, $request) {
                    return $query;
                }
            ],
            'tags' => [
                'with' => 'tags',
                'callback' => function ($query, $request) {
                    return $query;
                }
            ],
            'reviews' => [
                'with' => 'reviews.user',
                'callback' => function ($query, $request) {
                    // Only include approved reviews
                    return $query->whereHas('reviews', function ($q) {
                        $q->where('approved', true);
                    });
                }
            ],
            'variations' => [
                'with' => 'variations',
                'callback' => function ($query, $request) {
                    return $query;
                }
            ],
            'media' => [
                'with' => 'media',
                'callback' => function ($query, $request) {
                    return $query;
                }
            ]
        ];
    }

    /**
     * Example of custom filter method that can be called directly
     */
    public function featured(BaseFormRequest $request)
    {
        return $this->listingInit(
            $request,
            function ($query) {
                return [$query->where('featured', true)];
            },
            [],
            false, // Don't include trashed for featured products
            null,
            ['featured_only' => true],
            ['category', 'brand'],
            null,
            true,
            'created_at',
            $this->getAvailableIncludes()
        );
    }

    /**
     * Example of using advanced filtering
     */
    public function search(BaseFormRequest $request)
    {
        return $this->listingInit(
            $request,
            function ($query) use ($request) {
                // Advanced search with multiple conditions
                if ($request->search_term) {
                    $query->where(function ($q) use ($request) {
                        $q->like('name', $request->search_term)
                          ->orLike('description', $request->search_term)
                          ->orLike('sku', $request->search_term)
                          ->orWhereHas('category', function ($cat) use ($request) {
                              $cat->like('name', $request->search_term);
                          });
                    });
                }

                return [$query];
            },
            [
                'search_term' => 'required|string|min:2|max:100',
                'filters' => 'nullable|array',
                'ranges' => 'nullable|array',
            ],
            false,
            null,
            null,
            ['category', 'brand'],
            null,
            false, // Disable default search since we have custom search
            'created_at',
            $this->getAvailableIncludes()
        );
    }
}
