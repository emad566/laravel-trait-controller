<?php

namespace EmadSoliman\LaravelTraitController\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use EmadSoliman\LaravelTraitController\Services\FailedValidation;

abstract class BaseController extends Controller
{
    /**
     * The model class name
     */
    protected string $model;

    /**
     * The resource class name
     */
    protected string $resource;

    /**
     * The request class name for validation
     */
    protected ?string $modelRequest = null;

    /**
     * Database table name
     */
    protected string $table = '';

    /**
     * Primary key column name
     */
    protected string $primaryKey = 'id';

    /**
     * Fillable columns for filtering
     */
    protected array $columns = [];

    /**
     * Columns to exclude from filtering
     */
    protected array $excludedColumns = [];

    /**
     * Additional data for responses
     */
    protected array $data = [];

    /**
     * Constructor to auto-configure the controller
     */
    public function __construct(?string $model = null, array $excludedColumns = [])
    {
        if ($model) {
            $this->model = $model;
            $this->excludedColumns = $excludedColumns;
            $modelInstance = new $this->model();
            $this->columns = $modelInstance->getFillable();

            if (!empty($this->excludedColumns)) {
                $this->columns = array_filter($this->columns, function($column) {
                    return !in_array($column, $this->excludedColumns);
                });
                $this->columns = array_values($this->columns);
            }

            // Remove date filter columns if they exist in fillable
            $this->columns = array_filter($this->columns, function($column) {
                return !in_array($column, ['date_from', 'date_to']);
            });

            $this->primaryKey = $modelInstance->getKeyName();
            $this->table = $modelInstance->getTable();

            // Auto-resolve resource and request classes
            $modelBaseName = class_basename($modelInstance);
            $this->resource = "App\\Http\\Resources\\{$modelBaseName}Resource";
            $this->modelRequest = "App\\Http\\Requests\\{$modelBaseName}Request";
        }
    }

    /**
     * Send a JSON response
     */
    public function sendResponse(
        bool $status = true,
        $data = null,
        string $message = '',
        $errors = null,
        int $code = 200,
        ?Request $request = null
    ): JsonResponse {
        // Filter out file uploads from request data to prevent serialization errors
        $requestData = null;
        if ($request && trait_controller_config('response.include_request_data', false)) {
            $requestData = $request->all();
            foreach ($requestData as $key => $value) {
                if ($request->hasFile($key)) {
                    $requestData[$key] = '[FILE]';
                }
            }
        }

        $response = [
            'status' => $status,
            'message' => $message,
            'data' => $data,
            'errors' => $status === true ? $errors : (
                count($errors ?? [], COUNT_RECURSIVE) > 1 ? $errors : ['message' => [$message]]
            ),
        ];

        // Add optional response fields based on configuration
        if (trait_controller_config('response.include_response_code', false)) {
            $response['response_code'] = $code;
        }

        if ($requestData !== null) {
            $response['request_data'] = $requestData;
        }

        return response()->json($response, $code);
    }

    /**
     * Send a server error response
     */
    public function sendServerError(string $msg = '', $data = null, ?\Throwable $th = null): JsonResponse
    {
        $thStr = $th ? $th->getMessage() : '';
        return $this->sendResponse(false, $data, 'Server Technical Error: ' . $msg . " $thStr", null, 500);
    }

    /**
     * Check validator and return error response if validation fails
     */
    public function checkValidator($validator)
    {
        $failedValidation = new FailedValidation($validator);
        if ($failedValidation->status) {
            return $failedValidation->response;
        }
        return false;
    }
}
