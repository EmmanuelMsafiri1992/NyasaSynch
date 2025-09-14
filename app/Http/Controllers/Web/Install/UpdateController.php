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

namespace App\Http\Controllers\Web\Install;

// Increase the server resources
$iniConfigFile = __DIR__ . '/../../../Helpers/Functions/ini.php';
if (file_exists($iniConfigFile)) {
	include_once $iniConfigFile;
}

use App\Exceptions\Custom\AppVersionNotFound;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Install\Traits\Update\ApiTrait;
use App\Http\Controllers\Web\Install\Traits\Update\CleanUpTrait;
use App\Http\Controllers\Web\Install\Traits\Update\DbTrait;
use App\Http\Controllers\Web\Install\Traits\Update\EnvTrait;
use App\Http\Controllers\Web\Install\Traits\Update\LanguageTrait;
use App\Http\Controllers\Web\Install\Traits\Update\RoutesTrait;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class UpdateController extends Controller
{
	use CleanUpTrait, DbTrait, EnvTrait, RoutesTrait, LanguageTrait, ApiTrait;
	
	protected static bool $unsecuredUpgrade = false;
	
	/**
	 * UpdateController constructor.
	 *
	 * @throws \App\Exceptions\Custom\CustomException
	 */
	public function __construct()
	{
		$this->setDatabaseConfigIfMissing();
		if (!$this->areDatabaseUpToDate()) {
			self::$unsecuredUpgrade = true;
		}

		// When admin user(s) cannot be found, then allow unsecured upgrade
		if (!$this->isAdminUserCanBeFound()) {
			self::$unsecuredUpgrade = true;
		}

		// Apply middleware
		$this->applyMiddleware();
	}
	
	/**
	 * Apply middleware to the controller.
	 */
	protected function applyMiddleware(): void
	{
		if (!env('UNSECURED_UPGRADE', self::$unsecuredUpgrade)) {
			$this->middleware('admin');
		}
	}
	
	/**
	 * Start Upgrade
	 * URL: /upgrade
	 *
	 * @return \Illuminate\Contracts\View\View
	 */
	public function index()
	{
		return appView('install.update');
	}
	
	/**
	 * Run the Upgrade
	 * URL: /upgrade/run
	 *
	 * @return \Illuminate\Http\RedirectResponse
	 * @throws \App\Exceptions\Custom\AppVersionNotFound
	 * @throws \App\Exceptions\Custom\CustomException
	 */
	public function run(): \Illuminate\Http\RedirectResponse
	{
		// Lunch the installation if the /.env file doesn't exist
		if (!File::exists(base_path('.env'))) {
			return redirect()->to('/install');
		}
		
		// Get eventual new version value & the current (installed) version value
		$lastVersion = getLatestVersion();
		$currentVersion = getCurrentVersion();
		
		// All is up-to-date
		if (version_compare($lastVersion, $currentVersion, '<=')) {
			// If unsecured upgrade is allowed due to Admin User permission issue,
			// then, fix the Admin User Permissions
			if (self::$unsecuredUpgrade) {
				$this->fixAdminUserPermissions();
			}
			
			$message = 'You website is up-to-date! ';
			$message .= 'Nyasajob v' . $lastVersion . ' is currently the newest version available.';
			flash($message)->info();
			
			return redirect()->to('/');
		}
		
		// Installed version number is NOT found
		if (version_compare('1.0.0', $currentVersion, '>')) {
			$message = 'Cannot find the app\'s current version number in the "/.env" file.';
			throw new AppVersionNotFound($message);
		}
		
		// Go to maintenance with DOWN status
		Artisan::call('down');
		
		// Clear all the cache
		$this->clearCache();
		
		// Update files & Upgrade the database
		$this->applyUpdateChanges($lastVersion, $currentVersion);
		
		// If unsecured upgrade is allowed due to Admin User permission issue,
		// then, fix the Admin User Permissions
		if (self::$unsecuredUpgrade) {
			$this->fixAdminUserPermissions();
		}
		
		// (Try to) Sync. the multi-country URLs with the dynamics routes
		$this->syncMultiCountryUrlsAndRoutes();
		
		// Update the current version to last version
		$this->setCurrentVersion($lastVersion);
		
		// (Try to) Fill the missing lines in all languages files
		$this->syncLanguageFilesLines();
		
		// Check the Purchase Code
		$this->checkPurchaseCode();
		
		// Clear all the cache
		$this->clearCache();
		
		// Restore system UP status
		Artisan::call('up');
		
		// Success message
		$successMessage = '<strong>Congratulations!</strong> Your website has been upgraded to v' . $lastVersion;
		flash($successMessage)->success();
		
		// Warning message
		$warningMessage = 'IMPORTANT: If you have installed plugins, you should also upgrade them to their latest version.';
		flash($warningMessage)->warning();
		
		// Redirection
		$oldDefaultCountryCode = config('settings.geo_location.default_country_code');
		if (empty(config('settings.localization.default_country_code', $oldDefaultCountryCode))) {
			if (doesCountriesPageCanBeHomepage()) {
				return redirect()->to('/');
			} else {
				return redirect()->to(config('larapen.localization.countries_list_uri', '/'));
			}
		} else {
			return redirect()->to('/');
		}
	}
}
