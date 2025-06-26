<?php

namespace Emad566\LaravelTraitController\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class BaseFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation and apply sanitization
     */
    protected function prepareForValidation(): void
    {
        // Apply global input sanitization
        $this->sanitizeInputs();
    }

    /**
     * Sanitize all string inputs to prevent XSS and other vulnerabilities
     */
    protected function sanitizeInputs(): void
    {
        $sanitized = [];

        foreach ($this->all() as $key => $value) {
            $sanitized[$key] = $this->sanitizeValue($value);
        }

        $this->replace($sanitized);
    }

    /**
     * Recursively sanitize a value
     */
    protected function sanitizeValue($value)
    {
        if (is_string($value)) {
            // Remove NULL bytes
            $value = str_replace("\0", '', $value);

            // Trim whitespace
            $value = trim($value);

            // For HTML-containing fields, use more specific sanitization
            if ($this->shouldSanitizeHtml($value)) {
                $value = $this->sanitizeHtml($value);
            }

            // Limit string length to prevent excessive data
            $value = substr($value, 0, 10000); // Reasonable max length

            return $value;
        }

        if (is_array($value)) {
            return array_map([$this, 'sanitizeValue'], $value);
        }

        if (is_numeric($value)) {
            // Validate numeric values to prevent overflow attacks
            if (strlen((string)$value) > 20) {
                return 0; // Reset overly large numbers
            }
        }

        return $value;
    }

    /**
     * Check if a value should be HTML sanitized
     */
    protected function shouldSanitizeHtml(string $value): bool
    {
        return strpos($value, '<') !== false || strpos($value, '>') !== false;
    }

    /**
     * Sanitize HTML content
     */
    protected function sanitizeHtml(string $value): string
    {
        // Remove potentially dangerous HTML tags and attributes
        $value = strip_tags($value, '<p><br><strong><em><ul><ol><li>');
        $value = preg_replace('/javascript:/i', '', $value);
        $value = preg_replace('/on\w+\s*=/i', '', $value);

        return $value;
    }

    /**
     * Get validation rules for this request
     */
    public function rules(): array
    {
        return array_merge($this->getBaseSecurityRules(), $this->getCustomRules());
    }

    /**
     * Get base security validation rules applied to all requests
     */
    protected function getBaseSecurityRules(): array
    {
        return [
            // Prevent excessively long arrays
            '*' => 'max:1000', // Max 1000 items in any array
        ];
    }

    /**
     * Override this method in child classes to provide custom rules
     */
    protected function getCustomRules(): array
    {
        return [];
    }

    /**
     * Add global input validation with rate limiting and size checks
     */
    protected function getValidatorInstance()
    {
        $validator = parent::getValidatorInstance();

        // Add custom validation rules
        $this->addCustomValidationRules($validator);

        return $validator;
    }

    /**
     * Add custom validation rules for security
     */
    protected function addCustomValidationRules($validator): void
    {
        // SQL injection prevention for search terms
        $validator->addExtension('no_sql_injection', function ($attribute, $value, $parameters, $validator) {
            if (!is_string($value)) {
                return true;
            }

            // Check for common SQL injection patterns
            $sqlPatterns = [
                '/(\s|^)(union|select|insert|update|delete|drop|create|alter|exec|execute)\s/i',
                '/[\'"]\s*(or|and)\s*[\'"]/i',
                '/[\'"]\s*=\s*[\'"]/i',
                '/(;|\-\-|\/\*|\*\/)/i',
                '/\b(script|javascript|vbscript|onload|onerror|onclick)/i',
            ];

            foreach ($sqlPatterns as $pattern) {
                if (preg_match($pattern, $value)) {
                    return false;
                }
            }

            return true;
        });

        // XSS prevention
        $validator->addExtension('no_xss', function ($attribute, $value, $parameters, $validator) {
            if (!is_string($value)) {
                return true;
            }

            $xssPatterns = [
                '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
                '/javascript:/i',
                '/on\w+\s*=/i',
                '/<iframe/i',
                '/<object/i',
                '/<embed/i',
                '/expression\s*\(/i',
            ];

            foreach ($xssPatterns as $pattern) {
                if (preg_match($pattern, $value)) {
                    return false;
                }
            }

            return true;
        });

        // File path traversal prevention
        $validator->addExtension('no_path_traversal', function ($attribute, $value, $parameters, $validator) {
            if (!is_string($value)) {
                return true;
            }

            $pathPatterns = [
                '/\.\.\//',
                '/\.\.\\\\/',
                '/\.\./',
                '/\0/',
            ];

            foreach ($pathPatterns as $pattern) {
                if (preg_match($pattern, $value)) {
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * Handle a failed validation attempt
     */
    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors();
        $errorsArray = json_decode(json_encode($errors), true);

        $message = '';
        $errorsArrayValues = array_values($errorsArray);

        if (!empty($errorsArrayValues) && isset($errorsArrayValues[0][0])) {
            $message = $errorsArrayValues[0][0];
            if (count($errorsArrayValues ?? []) > 1) {
                $message .= ' and ' . (count($errorsArrayValues ?? []) - 1) . ' more validation errors';
            }
        }

        // Filter out file uploads and sensitive data to prevent information disclosure
        $input = $this->all();
        foreach ($input as $key => $value) {
            if ($this->hasFile($key)) {
                $input[$key] = '[FILE]';
            } elseif (in_array($key, ['password', 'password_confirmation', 'token'])) {
                $input[$key] = '[REDACTED]';
            }
        }

        $response = response()->json([
           'status' => false,
           'message' => $message,
           'data' => [],
           'errors' => $errorsArray,
           'input' => trait_controller_config('response.include_request_data', false) ? $input : null,
        ], 422);

        throw new ValidationException($validator, $response);
    }
}
