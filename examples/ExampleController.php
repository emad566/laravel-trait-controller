<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use EmadSoliman\LaravelTraitController\Controllers\BaseController;
use EmadSoliman\LaravelTraitController\Traits\IndexTrait;
use EmadSoliman\LaravelTraitController\Traits\ShowTrait;
use EmadSoliman\LaravelTraitController\Traits\EditTrait;
use EmadSoliman\LaravelTraitController\Traits\DestroyTrait;
use EmadSoliman\LaravelTraitController\Traits\ToggleActiveTrait;
use App\Models\Product;
use App\Http\Requests\FilterRequest;

/**
 * Example Product Controller using Laravel Trait Controller
 *
 * This controller demonstrates how to use the package traits
 * to quickly implement CRUD operations with advanced features.
 */
class ExampleController extends BaseController
{
    use IndexTrait, ShowTrait, EditTrait, DestroyTrait, ToggleActiveTrait;

    public function __construct()
    {
        // Auto-configure the controller for the Product model
        // Exclude 'internal_notes' from filtering
        parent::__construct(Product::class, ['internal_notes']);
    }

    /**
     * List products with filtering, pagination, and search
     */
    public function index(FilterRequest $request)
    {
        return $this->indexInit(
            $request,
            // Custom filtering callback
            function ($query) use ($request) {
                // Add custom filters
                if ($request->category_id) {
                    $query->where('category_id', $request->category_id);
                }

                if ($request->min_price) {
                    $query->where('price', '>=', $request->min_price);
                }

                if ($request->max_price) {
                    $query->where('price', '<=', $request->max_price);
                }

                return [$query];
            },
            // Additional validation rules
            [
                'category_id' => 'nullable|exists:categories,id',
                'min_price' => 'nullable|numeric|min:0',
                'max_price' => 'nullable|numeric|min:0',
            ],
            // Include soft deleted records (use helper function)
            should_include_trashed(),
            // After data retrieval callback
            function ($items) {
                // You can modify the collection here
                return [$items];
            },
            // Helper data for the response
            ['categories' => \App\Models\Category::all()],
            // Eager load relationships
            ['category', 'tags'],
            // Load additional relationships after pagination
            ['reviews']
        );
    }

    /**
     * Show a single product
     */
    public function show($id)
    {
        return $this->showInit($id, function ($item) {
            // Load additional relationships
            $item->load(['category', 'tags', 'reviews']);
            return [$item];
        });
    }

    /**
     * Get product data for editing
     */
    public function edit($id)
    {
        return $this->editInit($id, function ($item) {
            // Load relationships needed for editing
            $item->load(['category']);
            return [$item];
        });
    }

    /**
     * Create form data (called by editInit automatically)
     */
    public function create()
    {
        return $this->sendResponse(true, [
            'categories' => \App\Models\Category::all(),
            'tags' => \App\Models\Tag::all(),
        ], 'Create data retrieved');
    }

    /**
     * Store a new product
     */
    public function store(\App\Http\Requests\ProductRequest $request)
    {
        try {
            $product = Product::create($request->validated());

            return $this->sendResponse(true, [
                'item' => new \App\Http\Resources\ProductResource($product)
            ], 'Product created successfully', null, 201);

        } catch (\Throwable $th) {
            return $this->sendServerError('Error creating product', null, $th);
        }
    }

    /**
     * Update a product
     */
    public function update(\App\Http\Requests\ProductRequest $request, $id)
    {
        try {
            $product = Product::findOrFail($id);
            $product->update($request->validated());

            return $this->sendResponse(true, [
                'item' => new \App\Http\Resources\ProductResource($product->refresh())
            ], 'Product updated successfully');

        } catch (\Throwable $th) {
            return $this->sendServerError('Error updating product', null, $th);
        }
    }

    /**
     * Delete a product
     */
    public function destroy($id)
    {
        return $this->destroyInit($id, function ($item) {
            // Custom logic before deletion
            if ($item->orders()->count() > 0) {
                return [false, $this->sendResponse(false, [], 'Cannot delete product with existing orders', null, 422)];
            }
            return [$item];
        });
    }

    /**
     * Toggle product active status
     */
    public function toggleActive($id, $state)
    {
        return $this->toggleActiveInit($id, $state, function ($item) {
            // Custom logic before toggling
            if ($item->stock < 1 && $state === 'true') {
                return [false, $this->sendResponse(false, [], 'Cannot activate product with no stock', null, 422)];
            }
            return [$item];
        });
    }
}
