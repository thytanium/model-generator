<?php

namespace Thytanium\ModelGenerator;

use Illuminate\Support\ServiceProvider;

class ModelGeneratorServiceProvider extends ServiceProvider
{
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // TODO: Implement register() method.
        $this->app->bind('ModelGenerator',
        /**
         * @param $app
         * @return ModelGenerator
         */
        function ($app) {
            return new ModelGenerator($app['files']);
        });
    }

    /**
     * Boot the service provider
     *
     * @return void
     */
    public function boot()
    {
        //Register commands
        $this->commands([
            'Thytanium\ModelGenerator\Commands\GenerateModels',
        ]);

        //Set path for files
        $this->app->ModelGenerator->setPath(
            $this->app->basePath()
        );
    }

    /**
     * Get the services provided by the provider
     * @return array
     */
    public function provides()
    {
        return ['Thytanium\ModelGenerator\ModelGenerator'];
    }
}