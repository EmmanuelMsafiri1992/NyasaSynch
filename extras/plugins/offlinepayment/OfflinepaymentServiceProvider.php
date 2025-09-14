<?php

namespace extras\plugins\offlinepayment;

use Illuminate\Support\ServiceProvider;

class OfflinepaymentServiceProvider extends ServiceProvider
{
	/**
	 * Register any package services.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->bind('offlinepayment', function ($app) {
			return new Offlinepayment($app);
		});
	}
	
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        // Load plugin views
        $this->loadViewsFrom(realpath(__DIR__ . '/resources/views'), 'payment');

        // Load plugin languages files
		$this->loadTranslationsFrom(realpath(__DIR__ . '/lang'), 'offlinepayment');

        // Merge plugin config
        $this->mergeConfigFrom(realpath(__DIR__ . '/config.php'), 'payment');
    }
}
