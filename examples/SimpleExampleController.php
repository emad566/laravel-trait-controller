<?php

/**
 * SIMPLE EXAMPLE CONTROLLER - FOR REFERENCE ONLY
 *
 * This is a simple example controller demonstrating basic usage of the Laravel Trait Controller package.
 * Copy this file to your Laravel application and modify it according to your needs.
 */

namespace App\Http\Controllers;


use Emad566\LaravelTraitController\Controllers\BaseController;
use Emad566\LaravelTraitController\Traits\ListingTrait;
use Emad566\LaravelTraitController\Traits\RetrievalTrait;
use Emad566\LaravelTraitController\Traits\EditFormTrait;
use Emad566\LaravelTraitController\Traits\DeletionTrait;
use Emad566\LaravelTraitController\Traits\StatusToggleTrait;
use Emad566\LaravelTraitController\Http\Requests\BaseFormRequest;
use Emad566\LaravelTraitController\Http\Requests\FilterRequest;

/**
 * Simple Product Controller demonstrating basic usage
 * of the Laravel Trait Controller package
 */
class SimpleExampleController extends BaseController
{
    use ListingTrait, RetrievalTrait, EditFormTrait, DeletionTrait, StatusToggleTrait;

    public function __construct()
    {
        // Replace with your actual model class
        // parent::__construct(\App\Models\Product::class, ['sensitive_field']);
    }

    /**
     * List items with FilterRequest
     */
    public function index(FilterRequest $request)
    {
        return $this->listingInit($request);
    }

    /**
     * Show single item
     */
    public function show($id)
    {
        return $this->retrievalInit($id);
    }

    /**
     * Get item for editing
     */
    public function edit($id)
    {
        return $this->editFormInit($id);
    }

    /**
     * Delete item
     */
    public function destroy($id)
    {
        return $this->deletionInit($id);
    }

    /**
     * Toggle item status
     */
    public function toggleStatus($id, $state)
    {
        return $this->statusToggleInit($id, $state);
    }

    /**
     * Advanced listing with custom logic
     */
    public function advancedIndex(FilterRequest $request)
    {
        return $this->listingInit(
            $request,
            // Custom query modifications
            function ($query) use ($request) {
                // Add your custom filtering logic here
                return [$query];
            },
            // Additional validation rules
            ['custom_field' => 'nullable|string|max:255'],
            // Include soft deleted
            false,
            // After retrieval callback
            function ($items) {
                // Process items after retrieval
                return [$items];
            },
            // Helper data
            ['additional_data' => 'value'],
            // Eager load relationships
            ['relation1', 'relation2'],
            // Load after pagination
            ['relation3'],
            // Enable global search
            true,
            // Timestamp column for date filtering
            'created_at'
        );
    }
}
