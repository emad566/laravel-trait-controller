<?php

namespace Emad566\LaravelTraitController\Traits;

use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\SoftDeletes;
use Emad566\LaravelTraitController\Helpers\CustomLogger;

trait DeletionTrait
{
    /**
     * Initialize the deletion functionality
     */
    public function deletionInit($id, ?callable $processingCallback = null, ?bool $includeTrashed = null)
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

            $item = $this->model::where($this->primaryKey, $id)->first();
            if (!$item) {
                return $this->sendResponse(false, [], 'Item not found or inactive: ' . $id, null, 404);
            }

            // Custom callback for additional processing before deletion
            if ($processingCallback) {
                $response = $processingCallback($item);
                if ($response[0] === false) return $response[1];
                $item = $response[0];
            }

            $oldItem = clone $item;

            // Determine deletion method based on configuration
            if (should_force_delete() || !in_array(SoftDeletes::class, class_uses($this->model))) {
                $item->forceDelete();
                $deletionType = 'force_deleted';
            } else {
                $item->delete();
                $deletionType = 'soft_deleted';
            }

            // Log the deletion for debugging/audit trail
            CustomLogger::info('trait_controller_deletions.log', 'Item Deleted', [
                'model' => $this->model,
                'id' => $id,
                'deletion_type' => $deletionType,
                'deleted_by' => auth()->id() ?? 'system'
            ], true);

            return $this->sendResponse(true, [
                'item' => new $this->resource($oldItem),
            ], 'Item deleted successfully');

        } catch (\Throwable $th) {
            CustomLogger::error('trait_controller_errors.log', 'Deletion Error', [
                'model' => $this->model,
                'id' => $id,
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString()
            ]);

            return $this->sendServerError('Technical Error', null, $th);
        }
    }
}
