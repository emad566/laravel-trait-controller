<?php

namespace Emad566\LaravelTraitController\Traits;

use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\SoftDeletes;
use Emad566\LaravelTraitController\Helpers\CustomLogger;

trait RetrievalTrait
{
    /**
     * Initialize the single record retrieval functionality
     */
    public function retrievalInit($id, ?callable $processingCallback = null, ?bool $includeTrashed = null)
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
                return $this->sendResponse(false, [], 'Item not found or inactive', null, 404);
            }

            // Custom callback for additional processing
            if ($processingCallback) {
                $response = $processingCallback($item);
                if ($response[0] === false) return $response[1];
                $item = $response[0];
            }

            // Log the retrieval for debugging if enabled
            CustomLogger::debug('trait_controller_queries.log', 'Item Retrieved', [
                'model' => $this->model,
                'id' => $id,
                'found' => true
            ]);

            return $this->sendResponse(true, [
                'item' => new $this->resource($item),
            ], 'Item retrieved successfully');

        } catch (\Throwable $th) {
            CustomLogger::error('trait_controller_errors.log', 'Retrieval Error', [
                'model' => $this->model,
                'id' => $id,
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString()
            ]);

            return $this->sendServerError('Technical Error', null, $th);
        }
    }
}
