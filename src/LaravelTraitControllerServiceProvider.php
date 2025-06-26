<?php

namespace EmadSoliman\LaravelTraitController;

use Illuminate\Support\ServiceProvider;

class LaravelTraitControllerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge package configuration
        $this->mergeConfigFrom(
            __DIR__.'/../config/trait-controller.php', 'trait-controller'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__.'/../config/trait-controller.php' => config_path('trait-controller.php'),
        ], 'trait-controller-config');

        // Register macros for query builder
        $this->registerQueryBuilderMacros();
    }

    /**
     * Register custom query builder macros
     */
    protected function registerQueryBuilderMacros(): void
    {
        // Add like and likeStart macros to query builder
        if (!method_exists(\Illuminate\Database\Query\Builder::class, 'like')) {
            \Illuminate\Database\Query\Builder::macro('like', function ($column, $value) {
                return $this->where($column, 'LIKE', "%{$value}%");
            });
        }

        if (!method_exists(\Illuminate\Database\Query\Builder::class, 'likeStart')) {
            \Illuminate\Database\Query\Builder::macro('likeStart', function ($column, $value) {
                return $this->where($column, 'LIKE', "{$value}%");
            });
        }

        if (!method_exists(\Illuminate\Database\Query\Builder::class, 'orLike')) {
            \Illuminate\Database\Query\Builder::macro('orLike', function ($column, $value) {
                return $this->orWhere($column, 'LIKE', "%{$value}%");
            });
        }

        // Add the same macros to Eloquent Builder
        if (!method_exists(\Illuminate\Database\Eloquent\Builder::class, 'like')) {
            \Illuminate\Database\Eloquent\Builder::macro('like', function ($column, $value) {
                return $this->where($column, 'LIKE', "%{$value}%");
            });
        }

        if (!method_exists(\Illuminate\Database\Eloquent\Builder::class, 'likeStart')) {
            \Illuminate\Database\Eloquent\Builder::macro('likeStart', function ($column, $value) {
                return $this->where($column, 'LIKE', "{$value}%");
            });
        }

        if (!method_exists(\Illuminate\Database\Eloquent\Builder::class, 'orLike')) {
            \Illuminate\Database\Eloquent\Builder::macro('orLike', function ($column, $value) {
                return $this->orWhere($column, 'LIKE', "%{$value}%");
            });
        }
    }
}
