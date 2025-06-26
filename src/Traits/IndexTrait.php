<?php

namespace EmadSoliman\LaravelTraitController\Traits;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\SoftDeletes;

trait IndexTrait
{
    /**
     * Initialize the index/list functionality
     */
    public function indexInit(
        Request $request,
        ?callable $callback = null,
        array $validations = [],
        bool $includeTrashed = null,
        ?callable $afterGet = null,
        ?array $helpers = null,
        ?array $with = null,
        ?array $load = null,
        bool $enableSearch = true,
        string $createdAtColumn = 'created_at'
    ) {
        try {
            // Use configuration default if not provided
            if ($includeTrashed === null) {
                $includeTrashed = should_include_trashed();
            }

            $validator = Validator::make($request->all(), [
                ...trait_controller_config('list_validations', []),
                'sort_column' => 'nullable|in:id,' . implode(',', $this->columns),
                ...$validations,
            ]);

            $check = $this->checkValidator($validator);
            if ($check) return $check;

            // Build base query
            if ($includeTrashed && in_array(SoftDeletes::class, class_uses($this->model))) {
                $items = $this->model::withTrashed()->orderBy(
                    $request->sortColumn ?? $this->primaryKey,
                    $request->sortDirection ?? trait_controller_config('sort.default_direction', 'DESC')
                );
            } else {
                $items = $this->model::orderBy(
                    $request->sortColumn ?? $this->primaryKey,
                    $request->sortDirection ?? trait_controller_config('sort.default_direction', 'DESC')
                );
            }

            // Date filtering
            if ($request->date_from) {
                $items = $items->where($createdAtColumn, '>=', Carbon::parse($request->date_from));
            }

            if ($request->date_to) {
                $items = $items->where($createdAtColumn, '<=', Carbon::parse($request->date_to));
            }

            // Custom callback before filtering
            if ($callback) {
                $response = $callback($items);
                if ($response[0] === false) return $response[1];
                $items = $response[0];
            }

            // Column-based filtering
            foreach ($this->columns as $column) {
                if ($request->$column) {
                    $where = (Str::contains($column, '_id') || $column == "id") ? 'where' : 'likeStart';
                    $items = $items->$where($column, $request->$column);
                }
            }

            // General search functionality
            if ($request->q && $enableSearch) {
                $searchable = $this->columns;
                $items = $items->where(function ($query) use ($request, $searchable) {
                    foreach ($searchable as $column) {
                        $query->orLike($column, $request->q);
                    }
                    return $query;
                });
            }

            // Eager loading
            if ($with) {
                $items = $items->with($with);
            }

            // Pagination
            $perPage = $request->per_page ?? trait_controller_config('pagination.per_page', 15);
            $items = $items->paginate($perPage);

            // Load relationships after pagination
            if ($load) {
                $items->load($load);
            }

            // Custom callback after getting data
            if ($afterGet) {
                $response = $afterGet($items);
                if ($response[0] === false) return $response[1];
                $items = $response[0];
            }

            return $this->sendResponse(
                true,
                [
                    'helpers' => $helpers,
                    'items' => $this->resource::collection($items)->response()->getData(true)
                ],
                trans('Listed'),
                null,
                200,
                $request
            );

        } catch (\Throwable $th) {
            return $this->sendResponse(false, null, trans('Technical Error'), null, 500, $request);
        }
    }
}
