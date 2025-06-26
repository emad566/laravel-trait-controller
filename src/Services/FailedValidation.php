<?php

namespace Emad566\LaravelTraitController\Services;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\JsonResponse;

class FailedValidation
{
    public bool $status = false;
    public ?JsonResponse $response = null;

    public function __construct(Validator $validator)
    {
        if ($validator->fails()) {
            $this->status = true;
            $this->response = response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'data' => null,
                'errors' => $validator->errors(),
            ], 422);
        }
    }
}
