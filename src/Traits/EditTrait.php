<?php

namespace EmadSoliman\LaravelTraitController\Traits;

use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\SoftDeletes;

trait EditTrait
{
    /**
     * Initialize the edit functionality
     */
    public function editInit($id, ?callable $callback = null, bool $includeTrashed = null)
    {
        try {
            // Use configuration default if not provided
            if ($includeTrashed === null) {
                $includeTrashed = should_include_trashed();
            }

            $validator = Validator::make([$this->primaryKey => $id], [
                $this->primaryKey => 'required|exists:' . $this->table . ',' . $this->primaryKey,
            ]);

            $check = $this->checkValidator($validator);
            if ($check) return $check;

            $query = $this->model::select();
            if ($includeTrashed && in_array(SoftDeletes::class, class_uses($this->model))) {
                $query = $query->withTrashed();
            }

            $item = $query->where($this->primaryKey, $id)->first();

            if (!$item) {
                return $this->sendResponse(false, [], 'This Item is Inactive', null, 403);
            }

            // Custom callback for additional processing
            if ($callback) {
                $response = $callback($item);
                if ($response[0] === false) return $response[1];
                $item = $response[0];
            }

            // Get create data for dropdowns, options, etc.
            $createData = null;
            if (method_exists($this, 'create')) {
                $createResponse = $this->create();
                $createData = $createResponse->getData()?->data;
            }

            return $this->sendResponse(true, [
                'create' => $createData,
                'item' => new $this->resource($item),
            ], 'Edit data retrieved successfully');

        } catch (\Throwable $th) {
            return $this->sendServerError('Technical Error', null, $th);
        }
    }
}
