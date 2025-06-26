<?php

namespace Emad566\LaravelTraitController\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\SoftDeletes;
use Emad566\LaravelTraitController\Helpers\CustomLogger;

trait StatusToggleTrait
{
    /**
     * Initialize the status toggle functionality
     */
    public function statusToggleInit($id, $state, ?callable $processingCallback = null, ?bool $includeTrashed = null)
    {
        try {
            // Use configuration default if not provided
            if ($includeTrashed === null) {
                $includeTrashed = should_include_trashed();
            }

            $validator = Validator::make([
                $this->primaryKey => $id,
                'state' => $state
            ], [
                $this->primaryKey => 'required|exists:' . $this->table . ',' . $this->primaryKey,
                'state' => 'required|in:true,false,1,0'
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

            $previousState = $this->getCurrentState($item);

            // Toggle active state based on the model's capabilities
            $this->toggleItemState($item, $state);

            $newState = $this->getCurrentState($item);

            // Log the status change for audit trail
            CustomLogger::info('trait_controller_status_changes.log', 'Status Toggled', [
                'model' => $this->model,
                'id' => $id,
                'previous_state' => $previousState,
                'new_state' => $newState,
                'requested_state' => $state,
                'changed_by' => auth()->id() ?? 'system'
            ], true);

            return $this->sendResponse(true, [
                'item' => new $this->resource($item->refresh()),
                'previous_state' => $previousState,
                'new_state' => $newState
            ], 'Status toggled successfully');

        } catch (\Throwable $th) {
            CustomLogger::error('trait_controller_errors.log', 'Status Toggle Error', [
                'model' => $this->model,
                'id' => $id,
                'state' => $state,
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString()
            ]);

            return $this->sendServerError('Technical Error', null, $th);
        }
    }

    /**
     * Toggle the item state based on model capabilities
     */
    protected function toggleItemState($item, $state): void
    {
        $isActive = in_array($state, ['true', '1', 1, true]);

        // If model uses soft deletes, toggle via deleted_at
        if (in_array(SoftDeletes::class, class_uses($this->model))) {
            $item->update([
                'deleted_at' => $isActive ? null : Carbon::now()
            ]);
            return;
        }

        // Look for common status columns
        $statusColumns = ['active', 'status', 'is_active', 'enabled'];
        $fillableColumns = $item->getFillable();

        foreach ($statusColumns as $column) {
            if (in_array($column, $fillableColumns)) {
                $value = $this->getStatusValue($column, $isActive);
                $item->update([$column => $value]);
                return;
            }
        }

        // If no suitable column found, throw exception
        throw new \Exception('No suitable status column found for toggling. Model must use soft deletes or have an active/status column.');
    }

    /**
     * Get the appropriate value for the status column
     */
    protected function getStatusValue(string $column, bool $isActive)
    {
        // For status columns that might use different value types
        if ($column === 'status') {
            return $isActive ? 'active' : 'inactive';
        }

        // For boolean-like columns
        return $isActive ? 1 : 0;
    }

    /**
     * Get the current state of the item
     */
    protected function getCurrentState($item): string
    {
        // Check soft deletes first
        if (in_array(SoftDeletes::class, class_uses($this->model))) {
            return is_null($item->deleted_at) ? 'active' : 'inactive';
        }

        // Check status columns
        $statusColumns = ['active', 'status', 'is_active', 'enabled'];
        $fillableColumns = $item->getFillable();

        foreach ($statusColumns as $column) {
            if (in_array($column, $fillableColumns) && isset($item->$column)) {
                $value = $item->$column;

                if ($column === 'status') {
                    return $value === 'active' ? 'active' : 'inactive';
                }

                return $value ? 'active' : 'inactive';
            }
        }

        return 'unknown';
    }
}
