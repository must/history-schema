<?php

namespace Visionerp\HistorySchema;

use Illuminate\Support\ServiceProvider;

class HistorySchemaServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('history-schema', function () {
            return new HistorySchema;
        });
    }
}
