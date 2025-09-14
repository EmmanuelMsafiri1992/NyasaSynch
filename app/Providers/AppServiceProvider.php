<?php
/*
 * Nyasajob - Job Board Web Application
 * Copyright (c) BeDigit. All Rights Reserved
 *
 * Website: https://laraclassifier.com/nyasajob
 * Author: BeDigit | https://bedigit.com
 *
 * LICENSE
 * -------
 * This software is furnished under a license and may be used and copied
 * only in accordance with the terms of such license and with the inclusion
 * of the above copyright notice. If you Purchased from CodeCanyon,
 * Please read the full License from here - https://codecanyon.net/licenses/standard
 */

namespace App\Providers;

use App\Models\Sanctum\PersonalAccessToken;
use App\Providers\AppService\AclSystemTrait;
use App\Providers\AppService\ConfigTrait;
use App\Providers\AppService\SymlinkTrait;
use App\Providers\AppService\TelescopeTrait;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
	use TelescopeTrait, AclSystemTrait, ConfigTrait, SymlinkTrait;
	
	private int $cacheExpiration = 86400; // Cache for 1 day (60 * 60 * 24)
	
	/**
	 * Register any application services.
	 *
	 * @return void
	 */
	public function register()
	{
		// Register custom Artisan commands
		if ($this->app->runningInConsole()) {
			$this->commands([
				\App\Console\Commands\SiteInfoCommand::class,
				\App\Console\Commands\ListingsPurge::class,
				\App\Console\Commands\InstallFreshData::class,
			]);
		}
	}
	
	/**
	 * Bootstrap any application services.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->runInspection();
		
		// Set Bootstrap as default client assets
		Paginator::useBootstrap();
		
		// Specified key was too long error
		try {
			Schema::defaultStringLength(191);
		} catch (\Throwable $e) {
		}
		
		// Setup Laravel Sanctum
		try {
			Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
		} catch (\Throwable $e) {
		}
		
		// Setup Storage Symlink
		$this->setupStorageSymlink();
		
		// Setup ACL system
		$this->setupAclSystem();
		
		// Setup Https
		$this->setupHttps();
		
		// Setup Configs
		$this->setupConfigs();
		
		// Rate Limiters
		$this->setupRateLimiting();
		
		// Send Mails Always To
		$this->setupMailsAlwaysTo();
	}
	
	/**
	 * Setup Https
	 */
	private function setupHttps()
	{
		// Force HTTPS protocol
		if (config('larapen.core.forceHttps')) {
			URL::forceScheme('https');
		}
	}
	
	/**
	 * Configure the rate limiters for the application.
	 */
	private function setupRateLimiting(): void
	{
		// More Info: https://laravel.com/docs/10.x/routing#rate-limiting
		
		// API rate limit
		RateLimiter::for('api', function (Request $request) {
			// Exception for local and demo environments
			if (isLocalEnv() || isDemoEnv()) {
				return isLocalEnv()
					? Limit::none()
					: (
					$request->user()
						? Limit::perMinute(90)->by($request->user()->id)
						: Limit::perMinute(60)->by($request->ip())
					);
			}
			
			// Limits access to the routes associated with it to:
			// - (For logged users): 1200 requests per minute by user ID
			// - (For guests): 600 requests per minute by IP address
			return $request->user()
				? Limit::perMinute(1200)->by($request->user()->id)
				: Limit::perMinute(600)->by($request->ip());
		});
		
		// Global rate limit (Not used)
		RateLimiter::for('global', function (Request $request) {
			// Limits access to the routes associated with it to:
			// - 1000 requests per minute
			return Limit::perMinute(1000);
		});
	}
}
