<?php

namespace Xolluteon\Wooxali;

use Illuminate\Support\ServiceProvider;

class WooxaliServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/wooxali.php' => config_path('wooxali.php'),
        ], 'wooxali_config');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
