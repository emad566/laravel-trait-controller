<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the default configuration for Laravel Trait Controller
    | package. You can override these settings by publishing and modifying
    | the configuration file.
    |
    */

    /**
     * Default pagination settings
     */
    'pagination' => [
        'per_page' => env('TRAIT_CONTROLLER_PER_PAGE', 15),
        'max_per_page' => env('TRAIT_CONTROLLER_MAX_PER_PAGE', 100),
        'max_page' => env('TRAIT_CONTROLLER_MAX_PAGE', 1000),
    ],

    /**
     * Default validation rules for list endpoints
     */
    'list_validations' => [
        'per_page' => 'nullable|numeric|min:1|max:100',
        'page' => 'nullable|numeric|min:1|max:1000',
        'sort_direction' => 'nullable|in:ASC,DESC',
        'date_from' => 'nullable|date',
        'date_to' => 'nullable|date',
        'q' => 'nullable|string|max:255',
    ],

    /**
     * Default sort configuration
     */
    'sort' => [
        'default_column' => 'id',
        'default_direction' => 'DESC',
    ],

    /**
     * Cache configuration
     */
    'cache' => [
        'enabled' => env('TRAIT_CONTROLLER_CACHE_ENABLED', false),
        'ttl' => env('TRAIT_CONTROLLER_CACHE_TTL', 3600), // 1 hour in seconds
        'prefix' => env('TRAIT_CONTROLLER_CACHE_PREFIX', 'trait_controller'),
    ],

    /**
     * Response configuration
     */
    'response' => [
        'include_request_data' => env('TRAIT_CONTROLLER_INCLUDE_REQUEST_DATA', false),
        'include_response_code' => env('TRAIT_CONTROLLER_INCLUDE_RESPONSE_CODE', false),
    ],

    /**
     * Soft delete configuration
     */
    'soft_deletes' => [
        'force_delete_by_default' => env('TRAIT_CONTROLLER_FORCE_DELETE', false),
        'include_trashed_by_default' => env('TRAIT_CONTROLLER_INCLUDE_TRASHED', false),
    ],
];
