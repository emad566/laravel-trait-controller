<?php

if (!function_exists('trait_controller_config')) {
    /**
     * Get trait controller configuration value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function trait_controller_config(string $key, $default = null)
    {
        return config("trait-controller.{$key}", $default);
    }
}

if (!function_exists('should_include_trashed')) {
    /**
     * Determine if soft deleted records should be included
     * This can be overridden in your application
     *
     * @return bool
     */
    function should_include_trashed(): bool
    {
        return trait_controller_config('soft_deletes.include_trashed_by_default', false);
    }
}

if (!function_exists('should_force_delete')) {
    /**
     * Determine if records should be force deleted
     * This can be overridden in your application
     *
     * @return bool
     */
    function should_force_delete(): bool
    {
        return trait_controller_config('soft_deletes.force_delete_by_default', false);
    }
}
