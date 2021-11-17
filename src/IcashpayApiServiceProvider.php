<?php

namespace Icashpay\Api;

use Illuminate\Support\ServiceProvider;

class IcashpayApiServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
		
		$configPath = __DIR__ . '/../config/icashpay.php';
        $this->mergeConfigFrom($configPath, 'icashpay');
		
		$this->app->singleton('IcashpayApi', function () {
            return new IcashpayApi();
        });
		
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $source = realpath($raw = __DIR__ . '/../config/icashpay.php') ?: $raw;
        $this->publishes([
            $source => config_path('icashpay.php'),
        ]);
    }
}
