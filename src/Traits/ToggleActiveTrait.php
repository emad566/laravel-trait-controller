<?php

namespace EmadSoliman\LaravelTraitController\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\SoftDeletes;

trait ToggleActiveTrait
{
    /**
     * Initialize the toggle active functionality
     */
    public function toggleActiveInit($id, $state, ?callable $callback = null, bool $includeTrashed = null)
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

            // Toggle active state by updating deleted_at timestamp
            // This assumes the model uses soft deletes for active/inactive status
            if (in_array(SoftDeletes::class, class_uses($this->model))) {
                $item->update([
                    'deleted_at' => $state == 'true' ? null : Carbon::now()
                ]);
            } else {
                // If not using soft deletes, look for an 'active' or 'status' column
                $statusColumn = 'active';
                if (in_array('status', $item->getFillable())) {
                    $statusColumn = 'status';
                }

                if (in_array($statusColumn, $item->getFillable())) {
                    $item->update([
                        $statusColumn => $state == 'true' ? 1 : 0
                    ]);
                }
            }

            return $this->sendResponse(true, [
                'item' => new $this->resource($item->refresh()),
            ], 'Status toggled successfully');

        } catch (\Throwable $th) {
            return $this->sendServerError('Technical Error', null, $th);
        }
    }
}
