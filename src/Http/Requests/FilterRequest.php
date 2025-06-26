<?php

namespace EmadSoliman\LaravelTraitController\Http\Requests;

use Illuminate\Contracts\Validation\Validator;

class FilterRequest extends BaseFormRequest
{
    /**
     * Get custom validation rules for filtering and query parameters
     */
    protected function getCustomRules(): array
    {
        return [
            // Pagination rules
            'page' => 'nullable|integer|min:1|max:1000',
            'per_page' => 'nullable|integer|min:1|max:100',

            // Sorting rules
            'sortColumn' => 'nullable|string|max:50|alpha_dash|no_sql_injection',
            'sortDirection' => 'nullable|string|in:ASC,DESC,asc,desc',
            'sort_columns' => 'nullable|array|max:5',
            'sort_columns.*' => 'nullable|string|max:50|alpha_dash|no_sql_injection',
            'sort_directions' => 'nullable|array|max:5',
            'sort_directions.*' => 'nullable|string|in:ASC,DESC,asc,desc',

            // Date filtering
            'date_from' => 'nullable|date_format:Y-m-d\TH:i:s.u\Z|before:date_to',
            'date_to' => 'nullable|date_format:Y-m-d\TH:i:s.u\Z|after:date_from',
            'created_today' => 'nullable|boolean',
            'created_this_week' => 'nullable|boolean',
            'created_this_month' => 'nullable|boolean',
            'created_this_year' => 'nullable|boolean',

            // Text search rules
            'q' => 'nullable|string|max:255|no_xss|no_sql_injection|no_path_traversal',
            'name' => 'nullable|string|max:255|no_xss|no_sql_injection',
            'search_columns' => 'nullable|array|max:10',
            'search_columns.*' => 'nullable|string|max:50|alpha_dash',

            // Advanced filtering
            'filters' => 'nullable|array|max:20',
            'ranges' => 'nullable|array|max:10',
            'relationships' => 'nullable|array|max:10',
            'include' => 'nullable|string|max:500',

            // Category filtering
            'category_name' => 'nullable|string|max:255|no_xss|no_sql_injection',
            'category_names' => 'nullable|array|max:50',
            'category_names.*' => 'string|max:255|no_xss|no_sql_injection',
            'category_ids' => 'nullable|array|max:50',
            'category_ids.*' => 'integer|min:1',

            // Product filtering
            'product_name' => 'nullable|string|max:255|no_xss|no_sql_injection',
            'product_names' => 'nullable|array|max:50',
            'product_names.*' => 'string|max:255|no_xss|no_sql_injection',
            'product_ids' => 'nullable|array|max:50',
            'product_ids.*' => 'integer|min:1',

            // User filtering
            'user_name' => 'nullable|string|max:255|no_xss|no_sql_injection',
            'user_names' => 'nullable|array|max:50',
            'user_names.*' => 'string|max:255|no_xss|no_sql_injection',
            'user_ids' => 'nullable|array|max:50',
            'user_ids.*' => 'integer|min:1',

            // Price filtering
            'min_price' => 'nullable|numeric|min:0|max:999999.99',
            'max_price' => 'nullable|numeric|min:0|max:999999.99',
            'price_range' => 'nullable|array',
            'price_range.min' => 'nullable|numeric|min:0',
            'price_range.max' => 'nullable|numeric|min:0',

            // Status filtering
            'status' => 'nullable|string|max:50|alpha_dash|no_sql_injection',

            // Order filtering
            'order_number' => 'nullable|string|max:50|no_xss|no_sql_injection',

            // Generic ID filtering
            'id' => 'nullable|integer|min:1',
            'ids' => 'nullable|array|max:100',
            'ids.*' => 'integer|min:1',
        ];
    }

    protected function prepareForValidation()
    {
        parent::prepareForValidation();

        // Sanitize numeric parameters
        $numericFields = ['page', 'per_page', 'min_price', 'max_price', 'id'];
        foreach ($numericFields as $field) {
            if ($this->has($field) && $this->input($field) !== null) {
                $value = $this->input($field);
                if (is_numeric($value)) {
                    $this->merge([$field => in_array($field, ['page', 'per_page', 'id']) ? (int) $value : (float) $value]);
                }
            }
        }

        // Sanitize array parameters
        $arrayFields = [
            'category_names', 'category_ids', 'product_names', 'product_ids',
            'user_names', 'user_ids', 'ids', 'sort_columns', 'sort_directions',
            'search_columns'
        ];

        foreach ($arrayFields as $field) {
            if ($this->has($field) && is_array($this->input($field))) {
                $values = $this->input($field);

                // Limit array size based on field type
                $maxSize = str_contains($field, 'sort') ? 5 : (str_contains($field, 'search') ? 10 : 50);
                $values = array_slice($values, 0, $maxSize);

                // Clean array values
                $cleanValues = [];
                foreach ($values as $value) {
                    if (str_ends_with($field, '_ids') || $field === 'ids') {
                        // For ID arrays, ensure they're positive integers
                        if (is_numeric($value) && $value > 0) {
                            $cleanValues[] = (int) $value;
                        }
                    } elseif (str_contains($field, 'sort_directions')) {
                        // For sort directions, normalize to uppercase
                        if (in_array(strtoupper($value), ['ASC', 'DESC'])) {
                            $cleanValues[] = strtoupper($value);
                        }
                    } else {
                        // For other arrays, trim and validate strings
                        if (is_string($value) && strlen(trim($value)) > 0) {
                            $cleanValues[] = trim($value);
                        }
                    }
                }

                $this->merge([$field => $cleanValues]);
            }
        }

        // Normalize sort direction
        if ($this->has('sortDirection')) {
            $this->merge(['sortDirection' => strtoupper($this->input('sortDirection'))]);
        }

        // Handle price range object
        if ($this->has('price_range') && is_array($this->input('price_range'))) {
            $range = $this->input('price_range');
            $cleanRange = [];

            if (isset($range['min']) && is_numeric($range['min'])) {
                $cleanRange['min'] = (float) $range['min'];
            }

            if (isset($range['max']) && is_numeric($range['max'])) {
                $cleanRange['max'] = (float) $range['max'];
            }

            $this->merge(['price_range' => $cleanRange]);
        }

        // Convert boolean-like values
        $booleanFields = ['created_today', 'created_this_week', 'created_this_month', 'created_this_year'];
        foreach ($booleanFields as $field) {
            if ($this->has($field)) {
                $value = $this->input($field);
                $this->merge([$field => filter_var($value, FILTER_VALIDATE_BOOLEAN)]);
            }
        }
    }

    protected function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            // Validate pagination
            if ($this->has('per_page')) {
                $perPage = $this->input('per_page');
                if ($perPage > 100) {
                    $validator->errors()->add('per_page', 'Items per page cannot exceed 100.');
                }
            }

            // Validate search terms length
            $searchFields = ['q', 'name', 'category_name', 'product_name', 'user_name', 'order_number'];
            foreach ($searchFields as $field) {
                if ($this->has($field)) {
                    $value = $this->input($field);
                    if (is_string($value) && strlen($value) > 255) {
                        $validator->errors()->add($field, 'Search term is too long.');
                    }
                }
            }

            // Validate array sizes
            $arrayLimits = [
                'category_names' => 50, 'category_ids' => 50,
                'product_names' => 50, 'product_ids' => 50,
                'user_names' => 50, 'user_ids' => 50,
                'ids' => 100, 'sort_columns' => 5,
                'sort_directions' => 5, 'search_columns' => 10
            ];

            foreach ($arrayLimits as $field => $limit) {
                if ($this->has($field) && is_array($this->input($field))) {
                    $values = $this->input($field);
                    if (count($values) > $limit) {
                        $validator->errors()->add($field, "Too many values. Maximum {$limit} allowed.");
                    }
                }
            }

            // Validate price range - only when both min_price and max_price are provided
            if ($this->filled('min_price') && $this->filled('max_price')) {
                $minPrice = $this->input('min_price');
                $maxPrice = $this->input('max_price');
                if (is_numeric($minPrice) && is_numeric($maxPrice) && $minPrice > $maxPrice) {
                    $validator->errors()->add('max_price', 'Maximum price must be greater than or equal to minimum price.');
                }
            }

            // Validate price range object
            if ($this->has('price_range') && is_array($this->input('price_range'))) {
                $range = $this->input('price_range');
                if (isset($range['min']) && isset($range['max']) && $range['min'] > $range['max']) {
                    $validator->errors()->add('price_range.max', 'Maximum price must be greater than or equal to minimum price.');
                }
            }

            // Validate sort columns and directions match
            if ($this->has('sort_columns') && $this->has('sort_directions')) {
                $columns = $this->input('sort_columns');
                $directions = $this->input('sort_directions');

                if (is_array($columns) && is_array($directions) && count($columns) !== count($directions)) {
                    $validator->errors()->add('sort_directions', 'Sort directions must match the number of sort columns.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'page.integer' => 'Page must be a valid integer.',
            'page.min' => 'Page must be at least 1.',
            'page.max' => 'Page cannot exceed 1000.',
            'per_page.integer' => 'Items per page must be a valid integer.',
            'per_page.min' => 'Items per page must be at least 1.',
            'per_page.max' => 'Items per page cannot exceed 100.',
            'sortColumn.alpha_dash' => 'Sort column contains invalid characters.',
            'sortColumn.no_sql_injection' => 'Sort column contains potentially dangerous patterns.',
            'sortDirection.in' => 'Sort direction must be ASC or DESC.',
            'sort_columns.max' => 'Maximum 5 sort columns allowed.',
            'sort_directions.max' => 'Maximum 5 sort directions allowed.',
            'date_from.date_format' => 'Date from must be in valid ISO 8601 format.',
            'date_to.date_format' => 'Date to must be in valid ISO 8601 format.',
            'date_from.before' => 'Date from must be before date to.',
            'date_to.after' => 'Date to must be after date from.',
            'min_price.numeric' => 'Minimum price must be a valid number.',
            'min_price.min' => 'Minimum price cannot be negative.',
            'max_price.numeric' => 'Maximum price must be a valid number.',
            'max_price.gte' => 'Maximum price must be greater than or equal to minimum price.',
            'filters.max' => 'Maximum 20 filters allowed.',
            'ranges.max' => 'Maximum 10 ranges allowed.',
            'relationships.max' => 'Maximum 10 relationship filters allowed.',
            'search_columns.max' => 'Maximum 10 search columns allowed.',
            'include.max' => 'Include parameter is too long.',
            '*.no_xss' => 'This field contains potentially dangerous content.',
            '*.no_sql_injection' => 'This field contains potentially dangerous patterns.',
            '*.no_path_traversal' => 'This field contains invalid path characters.',
            '*.max' => 'This field is too long.',
            '*.integer' => 'This field must be a valid integer.',
            '*.min' => 'This field value is too small.',
        ];
    }
}
