<?php

namespace EmadSoliman\LaravelTraitController\Traits;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\SoftDeletes;
use EmadSoliman\LaravelTraitController\Helpers\CustomLogger;

trait ListingTrait
{
    /**
     * Initialize the listing functionality with advanced filtering
     */
    public function listingInit(
        Request $request,
        ?callable $beforeFilterCallback = null,
        array $additionalValidations = [],
        ?bool $includeTrashed = null,
        ?callable $afterRetrievalCallback = null,
        ?array $helperData = null,
        ?array $eagerLoadRelations = null,
        ?array $loadAfterPagination = null,
        bool $enableGlobalSearch = true,
        string $timestampColumn = 'created_at',
        array $includeOptions = []
    ) {
        try {
            // Use configuration default if not provided
            if ($includeTrashed === null) {
                $includeTrashed = should_include_trashed();
            }

            // Enhanced validation with advanced filtering rules
            $baseValidations = array_merge(
                trait_controller_config('list_validations', []),
                $this->getAdvancedFilterValidations(),
                $additionalValidations
            );

            $validator = Validator::make($request->all(), $baseValidations);

            $check = $this->checkValidator($validator);
            if ($check) return $check;

            // Build base query with advanced features
            $query = $this->buildBaseQuery($includeTrashed, $request);

            // Apply advanced date filtering
            $query = $this->applyDateFiltering($query, $request, $timestampColumn);

            // Apply custom callback before filtering
            if ($beforeFilterCallback) {
                $response = $beforeFilterCallback($query);
                if ($response[0] === false) return $response[1];
                $query = $response[0];
            }

            // Apply advanced column filtering
            $query = $this->applyAdvancedColumnFiltering($query, $request);

            // Apply range filtering (min/max values)
            $query = $this->applyRangeFiltering($query, $request);

            // Apply relationship filtering
            $query = $this->applyRelationshipFiltering($query, $request);

            // Apply advanced search functionality
            if ($enableGlobalSearch && $request->q) {
                $query = $this->applyAdvancedSearch($query, $request->q);
            }

            // Apply include options (like Laravel API resources)
            if (!empty($includeOptions)) {
                $query = $this->applyIncludeOptions($query, $request, $includeOptions);
            }

            // Apply sorting with multiple columns support
            $query = $this->applyAdvancedSorting($query, $request);

            // Eager loading
            if ($eagerLoadRelations) {
                $query = $query->with($eagerLoadRelations);
            }

            // Execute pagination
            $perPage = $this->calculatePerPage($request);
            $items = $query->paginate($perPage);

            // Load relationships after pagination
            if ($loadAfterPagination) {
                $items->load($loadAfterPagination);
            }

            // Apply post-retrieval callback
            if ($afterRetrievalCallback) {
                $response = $afterRetrievalCallback($items);
                if ($response[0] === false) return $response[1];
                $items = $response[0];
            }

            // Log the query for debugging if enabled
            CustomLogger::debug('trait_controller_queries.log', 'Listing Query Executed', [
                'model' => $this->model,
                'filters_applied' => $request->all(),
                'total_results' => $items->total()
            ]);

            return $this->sendResponse(
                true,
                [
                    'meta' => $this->buildResponseMeta($items),
                    'helpers' => $helperData,
                    'items' => $this->resource::collection($items)->response()->getData(true)
                ],
                'Items retrieved successfully',
                null,
                200,
                $request
            );

        } catch (\Throwable $th) {
            CustomLogger::error('trait_controller_errors.log', 'Listing Error', [
                'model' => $this->model,
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString()
            ]);

            return $this->sendResponse(false, null, 'Technical Error', null, 500, $request);
        }
    }

    /**
     * Get advanced filter validation rules
     */
    protected function getAdvancedFilterValidations(): array
    {
        return [
            'sort_columns' => 'nullable|array|max:5',
            'sort_columns.*' => 'nullable|in:id,' . implode(',', $this->columns),
            'sort_directions' => 'nullable|array|max:5',
            'sort_directions.*' => 'nullable|in:ASC,DESC',
            'filters' => 'nullable|array|max:20',
            'ranges' => 'nullable|array|max:10',
            'relationships' => 'nullable|array|max:10',
            'include' => 'nullable|string|max:500',
            'search_columns' => 'nullable|array|max:10',
            'search_columns.*' => 'nullable|in:' . implode(',', $this->columns),
        ];
    }

    /**
     * Build base query with soft delete handling
     */
    protected function buildBaseQuery(bool $includeTrashed, Request $request)
    {
        $defaultSortColumn = $request->sort_columns[0] ??
                           $request->sortColumn ??
                           $this->primaryKey;

        $defaultSortDirection = $request->sort_directions[0] ??
                              $request->sortDirection ??
                              trait_controller_config('sort.default_direction', 'DESC');

        if ($includeTrashed && in_array(SoftDeletes::class, class_uses($this->model))) {
            return $this->model::withTrashed()->orderBy($defaultSortColumn, $defaultSortDirection);
        }

        return $this->model::orderBy($defaultSortColumn, $defaultSortDirection);
    }

    /**
     * Apply advanced date filtering
     */
    protected function applyDateFiltering($query, Request $request, string $timestampColumn)
    {
        // Standard date range filtering
        if ($request->date_from) {
            $query = $query->where($timestampColumn, '>=', Carbon::parse($request->date_from));
        }

        if ($request->date_to) {
            $query = $query->where($timestampColumn, '<=', Carbon::parse($request->date_to));
        }

        // Advanced date filters
        if ($request->created_today) {
            $query = $query->whereDate($timestampColumn, Carbon::today());
        }

        if ($request->created_this_week) {
            $query = $query->whereBetween($timestampColumn, [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek()
            ]);
        }

        if ($request->created_this_month) {
            $query = $query->whereMonth($timestampColumn, Carbon::now()->month)
                           ->whereYear($timestampColumn, Carbon::now()->year);
        }

        if ($request->created_this_year) {
            $query = $query->whereYear($timestampColumn, Carbon::now()->year);
        }

        return $query;
    }

    /**
     * Apply advanced column filtering
     */
    protected function applyAdvancedColumnFiltering($query, Request $request)
    {
        // Standard column filtering
        foreach ($this->columns as $column) {
            if ($request->$column) {
                $whereMethod = $this->determineWhereMethod($column);
                $query = $query->$whereMethod($column, $request->$column);
            }
        }

        // Advanced filters array
        if ($request->filters && is_array($request->filters)) {
            foreach ($request->filters as $column => $value) {
                if (in_array($column, $this->columns) && $value !== null) {
                    $whereMethod = $this->determineWhereMethod($column);
                    $query = $query->$whereMethod($column, $value);
                }
            }
        }

        return $query;
    }

    /**
     * Apply range filtering for numeric/date fields
     */
    protected function applyRangeFiltering($query, Request $request)
    {
        if ($request->ranges && is_array($request->ranges)) {
            foreach ($request->ranges as $column => $range) {
                if (!in_array($column, $this->columns) || !is_array($range)) {
                    continue;
                }

                if (isset($range['min']) && $range['min'] !== null) {
                    $query = $query->where($column, '>=', $range['min']);
                }

                if (isset($range['max']) && $range['max'] !== null) {
                    $query = $query->where($column, '<=', $range['max']);
                }
            }
        }

        return $query;
    }

    /**
     * Apply relationship filtering
     */
    protected function applyRelationshipFiltering($query, Request $request)
    {
        if ($request->relationships && is_array($request->relationships)) {
            foreach ($request->relationships as $relation => $filters) {
                if (!is_array($filters)) continue;

                $query = $query->whereHas($relation, function ($subQuery) use ($filters) {
                    foreach ($filters as $column => $value) {
                        if ($value !== null) {
                            $whereMethod = $this->determineWhereMethod($column);
                            $subQuery->$whereMethod($column, $value);
                        }
                    }
                });
            }
        }

        return $query;
    }

    /**
     * Apply advanced search with configurable columns
     */
    protected function applyAdvancedSearch($query, string $searchTerm)
    {
        $searchColumns = request('search_columns', $this->columns);

        return $query->where(function ($subQuery) use ($searchTerm, $searchColumns) {
            foreach ($searchColumns as $column) {
                $subQuery->orLike($column, $searchTerm);
            }
        });
    }

    /**
     * Apply include options similar to Laravel API resources
     */
    protected function applyIncludeOptions($query, Request $request, array $includeOptions)
    {
        $includes = array_filter(explode(',', $request->include ?? ''));

        foreach ($includes as $include) {
            $include = trim($include);
            if (isset($includeOptions[$include])) {
                $config = $includeOptions[$include];

                if (isset($config['with'])) {
                    $query = $query->with($config['with']);
                }

                if (isset($config['callback']) && is_callable($config['callback'])) {
                    $query = $config['callback']($query, $request);
                }
            }
        }

        return $query;
    }

    /**
     * Apply advanced sorting with multiple columns
     */
    protected function applyAdvancedSorting($query, Request $request)
    {
        $sortColumns = $request->sort_columns ?? [];
        $sortDirections = $request->sort_directions ?? [];

        if (!empty($sortColumns)) {
            foreach ($sortColumns as $index => $column) {
                if (in_array($column, $this->columns)) {
                    $direction = $sortDirections[$index] ?? 'ASC';
                    $query = $query->orderBy($column, $direction);
                }
            }
        }

        return $query;
    }

    /**
     * Determine the appropriate where method based on column type
     */
    protected function determineWhereMethod(string $column): string
    {
        if (Str::contains($column, '_id') || $column === 'id') {
            return 'where';
        }

        if (Str::contains($column, ['email', 'url', 'slug'])) {
            return 'where';
        }

        return 'likeStart';
    }

    /**
     * Calculate per page value with validation
     */
    protected function calculatePerPage(Request $request): int
    {
        $perPage = $request->per_page ?? trait_controller_config('pagination.per_page', 15);
        $maxPerPage = trait_controller_config('pagination.max_per_page', 100);

        return min($perPage, $maxPerPage);
    }

    /**
     * Build response metadata
     */
    protected function buildResponseMeta($paginatedItems): array
    {
        return [
            'pagination' => [
                'current_page' => $paginatedItems->currentPage(),
                'per_page' => $paginatedItems->perPage(),
                'total' => $paginatedItems->total(),
                'last_page' => $paginatedItems->lastPage(),
                'from' => $paginatedItems->firstItem(),
                'to' => $paginatedItems->lastItem(),
            ],
            'filters_applied' => request()->only([
                'q', 'filters', 'ranges', 'relationships', 'include',
                'date_from', 'date_to', 'created_today', 'created_this_week',
                'created_this_month', 'created_this_year'
            ]),
            'available_includes' => array_keys($this->getAvailableIncludes()),
        ];
    }

    /**
     * Get available include options (override in controller)
     */
    protected function getAvailableIncludes(): array
    {
        return [];
    }
}
