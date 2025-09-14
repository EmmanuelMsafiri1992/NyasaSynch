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

use App\Helpers\Cookie;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Install\Traits\Install\ApiTrait;
use App\Http\Controllers\Web\Install\Traits\Install\CheckerTrait;
use App\Http\Controllers\Web\Install\Traits\Install\DbTrait;
use App\Http\Controllers\Web\Install\Traits\Install\EnvTrait;
use App\Http\Requests\Install\DatabaseInfoRequest;
use App\Http\Requests\Install\SiteInfoRequest;
use App\Providers\AppService\ConfigTrait\MailConfig;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class InstallController extends Controller
{
	use ApiTrait, CheckerTrait, MailConfig, EnvTrait, DbTrait;
	
	public string $baseUrl;
	public string $installUrl;
	public array $stepUrl;
	
	private ?string $defaultCountyCode = null;
	
	public function __construct()
	{
		$this->commonQueries();
		
		// Create SQL destination path if not exists
		$countriesDataDir = storage_path('app/database/geonames/countries');
		if (!File::exists($countriesDataDir)) {
			File::makeDirectory($countriesDataDir, 0755, true);
		}
		
		// Base URL
		$this->baseUrl = getRawBaseUrl();
		view()->share('baseUrl', $this->baseUrl);
		config()->set('app.url', $this->baseUrl);
		
		// Installation URL
		$this->installUrl = $this->baseUrl . '/install';
		view()->share('installUrl', $this->installUrl);
		
		// Installation Steps' URLs
		$this->stepUrl = [
			'compatibility'  => $this->installUrl . '/system_compatibility',
			'siteInfo'       => $this->installUrl . '/site_info',
			'databaseInfo'   => $this->installUrl . '/database_info',
			'databaseImport' => $this->installUrl . '/database_import',
			'cronJobs'       => $this->installUrl . '/cron_jobs',
			'finish'         => $this->installUrl . '/finish',
		];
		view()->share('stepUrl', $this->stepUrl);
	}
	
	/**
	 * Common Queries
	 *
	 * @return void
	 */
	public function commonQueries(): void
	{
		// Delete all front&back office sessions
		session()->forget('countryCode');
		session()->forget('timeZone');
		session()->forget('langCode');
		
		// Get country code by the user IP address
		// This method set its result in cookie (with the 'ipCountryCode' as key name)
		$this->defaultCountyCode = $this->getCountryCodeFromIPAddr();
	}
	
	/**
	 * Checking for the current step
	 *
	 * @return int
	 */
	public function step(): int
	{
		$step = 0;
		
		$data = session('compatibilityChecked');
		if (!empty($data)) {
			$step = 1;
		} else {
			return $step;
		}
		
		$data = session('siteInfo');
		if (!empty($data)) {
			$step = 2;
		} else {
			return $step;
		}
		
		$data = session('databaseInfo');
		if (!empty($data)) {
			$step = 3;
		} else {
			return $step;
		}
		
		$data = session('databaseImported');
		if (!empty($data)) {
			$step = 4;
		} else {
			return $step;
		}
		
		$data = session('cronJobsInfoSeen');
		if (!empty($data)) {
			$step = 5;
		} else {
			return $step;
		}
		
		return $step;
	}
	
	/**
	 * STEP 0 - Starting installation
	 *
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function starting(): RedirectResponse
	{
		Artisan::call('cache:clear');
		Artisan::call('config:clear');
		
		// Get possible query string
		$queryString = request()->getQueryString();
		$queryString = !empty($queryString) ? '?' . $queryString : '';
		
		return redirect()->to($this->stepUrl['compatibility'] . $queryString);
	}
	
	/**
	 * STEP 1 - Check System Compatibility
	 *
	 * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
	 */
	public function systemCompatibility(): View|RedirectResponse
	{
		session()->forget('compatibilityChecked');
		
		// Check Components & Permissions
		$checkComponents = $this->checkComponents();
		$checkPermissions = $this->checkPermissions();
		$isCompatible = $checkComponents && $checkPermissions;
		
		// 1. Auto-Checking: Skip this step If the system is OK
		$isCompatibleWithAutoRedirect = $isCompatible && !$this->isManualCheckingAllowed();
		if ($isCompatibleWithAutoRedirect) {
			session()->put('compatibilityChecked', ($isCompatible ? 1 : 0));
			
			// Get possible query string
			$queryString = request()->getQueryString();
			$queryString = !empty($queryString) ? '?' . $queryString : '';
			
			return redirect()->to($this->stepUrl['siteInfo'] . $queryString);
		}
		
		// 2. Check the compatibilities manually: Retry if something does not work yet
		try {
			if ($isCompatible) {
				session()->put('compatibilityChecked', 1);
			}
			
			return appView('install.compatibilities', [
				'components'       => $this->getComponents(),
				'permissions'      => $this->getPermissions(),
				'checkComponents'  => $checkComponents,
				'checkPermissions' => $checkPermissions,
				'step'             => $this->step(),
				'current'          => 1,
			]);
		} catch (\Throwable $e) {
			Artisan::call('cache:clear');
			Artisan::call('config:clear');
			
			return redirect()->to($this->stepUrl['compatibility']);
		}
	}
	
	/**
	 * STEP 2.1 - Set Site Info
	 *
	 * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
	 */
	public function siteInfo(): View|RedirectResponse
	{
		if ($this->step() < 1) {
			$message = trans('messages.compatibility_required');
			flash($message)->info();
			
			return redirect()->to($this->stepUrl['compatibility']);
		}
		
		// Remove the installed file (if it does exist)
		$installedFile = storage_path('installed');
		if (File::exists($installedFile)) {
			File::delete($installedFile);
		}
		
		// Unactivated all add-ons/plugins by removing their installed file
		$pluginsDir = storage_path('framework/plugins');
		$leaveFiles = ['.gitignore'];
		foreach (glob($pluginsDir . '/*') as $file) {
			if (!in_array(basename($file), $leaveFiles)) {
				@unlink($file);
			}
		}
		
		// Get Mail Drivers
		$mailDrivers = (array)config('larapen.options.mail');
		
		// Get the drivers selectors list as JS objects
		$mailDriversSelectorsJson = collect($mailDrivers)
			->keys()
			->mapWithKeys(fn ($item) => [$item => '.' . $item])
			->toJson();
		
		// Format the mail drivers list
		$mailDrivers = collect($mailDrivers)
			->mapWithKeys(fn ($item, $key) => [$key => ['value' => $key, 'text' => $item]])
			->toArray();
		
		// Retrieve site info
		$siteInfo = request()->old();
		$siteInfo = !empty($siteInfo) ? $siteInfo : session('siteInfo');
		
		return appView('install.site_info', [
			'defaultCountyCode'        => $this->defaultCountyCode,
			'siteInfo'                 => $siteInfo,
			'step'                     => $this->step(),
			'current'                  => 2,
			'mailDrivers'              => $mailDrivers,
			'mailDriversSelectorsJson' => $mailDriversSelectorsJson,
		]);
	}
	
	/**
	 * STEP 2.2 - Set Site Info
	 *
	 * @param \App\Http\Requests\Install\SiteInfoRequest $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function postSiteInfo(SiteInfoRequest $request): RedirectResponse
	{
		if ($this->step() < 1) {
			$message = trans('messages.compatibility_required');
			flash($message)->info();
			
			return redirect()->to($this->stepUrl['compatibility']);
		}
		
		// Clear old data from the session
		session()->forget('siteInfo');
		
		// Save the new data in session
		session()->put('siteInfo', $request->all());
		
		return redirect()->to($this->stepUrl['databaseInfo']);
	}
	
	/**
	 * STEP 3.1 - Database Configuration
	 *
	 * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
	 */
	public function databaseInfo(): View|RedirectResponse
	{
		if ($this->step() < 2) {
			$message = trans('messages.site_info_required');
			flash($message)->info();
			
			return redirect()->to($this->stepUrl['siteInfo']);
		}
		
		$databaseInfo = request()->old();
		$databaseInfo = !empty($databaseInfo) ? $databaseInfo : session('databaseInfo');
		
		return appView('install.database_info', [
			'databaseInfo' => $databaseInfo,
			'step'         => $this->step(),
			'current'      => 3,
		]);
	}
	
	/**
	 * STEP 3.2 - Submit Database Configuration
	 *
	 * @param \App\Http\Requests\Install\DatabaseInfoRequest $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function postDatabaseInfo(DatabaseInfoRequest $request): RedirectResponse
	{
		if ($this->step() < 2) {
			$message = trans('messages.site_info_required');
			flash($message)->info();
			
			return redirect()->to($this->stepUrl['siteInfo']);
		}
		
		// Clear old data from the session
		session()->forget('databaseInfo');
		
		// Get database info & site info
		$siteInfo = (array)session('siteInfo');
		$databaseInfo = $request->all();
		
		// Save the new data in session
		session()->put('databaseInfo', $databaseInfo);
		/*
		 * Ensure this session is saved before continuing
		 * i.e. Don't wait until the end of the request to let it be saved
		 */
		session()->save();
		
		// Write config file
		$this->writeEnv($siteInfo, $databaseInfo);
		
		// Notification Message
		$message = trans('messages.database_connection_success');
		flash($message)->success();
		
		// Return to Import Database page
		return redirect()->to($this->stepUrl['databaseImport'])
			->withHeaders(config('larapen.core.noCacheHeaders'));
	}
	
	/**
	 * STEP 4.1 - Import Database
	 *
	 * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
	 */
	public function databaseImport(): View|RedirectResponse
	{
		if ($this->step() < 3) {
			$message = trans('messages.database_info_required');
			flash($message)->info();
			
			return redirect()->to($this->stepUrl['databaseInfo']);
		}
		
		// Get the database info
		$databaseInfo = (array)session('databaseInfo');
		
		// Check if the database connection is ok
		try {
			$this->getPdoConnectionWithEnvCheck($databaseInfo);
		} catch (\Throwable $e) {
			flash($e->getMessage())->error();
			
			return redirect()->to($this->stepUrl['databaseInfo']);
		}
		
		return appView('install.database_import', [
			'databaseInfo' => $databaseInfo,
			'step'         => $this->step(),
			'current'      => 4,
		]);
	}
	
	/**
	 * STEP 4.2 - Submit Database Import
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function postDatabaseImport(Request $request): RedirectResponse
	{
		if ($this->step() < 3) {
			$message = trans('messages.database_info_required');
			flash($message)->info();
			
			return redirect()->to($this->stepUrl['databaseInfo']);
		}
		
		// Get database info & site info
		$siteInfo = (array)session('siteInfo');
		$databaseInfo = (array)session('databaseInfo');
		
		// Update the database info
		$databaseInfo['overwrite_tables'] = $request->input('overwrite_tables', '0');
		session()->put('databaseInfo', $databaseInfo);
		/*
		 * Ensure this session is saved before continuing
		 * i.e. Don't wait until the end of the request to let it be saved
		 */
		session()->save();
		
		// Clear old notification from the session
		session()->forget('databaseImported');
		
		try {
			
			// Import the required data
			$this->submitDatabaseImport($siteInfo, $databaseInfo);
			
		} catch (\Throwable $e) {
			flash($e->getMessage())->error();
			
			return redirect()->to($this->stepUrl['databaseImport'])->withInput($databaseInfo);
		}
		
		// The database is now imported!
		// Save the new notification in session
		session()->put('databaseImported', 1);
		
		// Notification Message
		$message = trans('messages.database_tables_configuration_success');
		flash($message)->success();
		
		return redirect()->to($this->stepUrl['cronJobs']);
	}
	
	/**
	 * STEP 5 - Set Cron Jobs
	 *
	 * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
	 */
	public function cronJobs(): View|RedirectResponse
	{
		if ($this->step() < 4) {
			$message = trans('messages.database_import_required');
			flash($message)->info();
			
			return redirect()->to($this->stepUrl['databaseImport']);
		}
		
		// Check if the database connection is ok
		try {
			$this->getPdoConnectionWithEnvCheck();
		} catch (\Throwable $e) {
			flash($e->getMessage())->error();
			
			return redirect()->to($this->stepUrl['databaseInfo']);
		}
		
		// The cron jobs config info is seen
		// Save the notification in session
		session()->put('cronJobsInfoSeen', 1);
		
		$phpBinaryPath = $this->getPhpBinaryPath();
		$requiredPhpVersion = $this->getComposerRequiredPhpVersion();
		
		return appView('install.cron_jobs', [
			'phpBinaryPath'      => $phpBinaryPath,
			'requiredPhpVersion' => $requiredPhpVersion,
			'basePath'           => base_path(),
			'step'               => $this->step(),
			'current'            => 5,
		]);
	}
	
	/**
	 * STEP 6 - Finish
	 *
	 * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
	 * @throws \App\Exceptions\Custom\CustomException
	 */
	public function finish(): View|RedirectResponse
	{
		if ($this->step() < 5) {
			$message = trans('messages.cron_jobs_required');
			flash($message)->info();
			
			return redirect()->to($this->stepUrl['cronJobs']);
		}
		
		// Check if the database connection is ok
		try {
			$this->getPdoConnectionWithEnvCheck();
		} catch (\Throwable $e) {
			flash($e->getMessage())->error();
			
			return redirect()->to($this->stepUrl['databaseInfo']);
		}
		
		// Create the "installed" file
		createTheInstalledFile(true);
		
		// Declare the installation as complete
		session()->put('installationCompleted', 1);
		/*
		 * Ensure this session is saved before continuing
		 * i.e. Don't wait until the end of the request to let it be saved
		 */
		session()->save();
		
		// Delete all front & back office cookies
		Cookie::forget('ipCountryCode');
		
		// Clear all the cache
		Artisan::call('cache:clear');
		sleep(2);
		Artisan::call('view:clear');
		sleep(1);
		File::delete(File::glob(storage_path('logs') . DIRECTORY_SEPARATOR . '*.log'));
		
		// Rendering final Info
		return appView('install.finish', [
			'step'    => $this->step(),
			'current' => 6,
		]);
	}
	
	// PRIVATE METHODS
	// Check out Traits
}
