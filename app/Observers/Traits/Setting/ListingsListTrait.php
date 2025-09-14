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

namespace App\Observers\Traits\Setting;

use App\Helpers\Cookie;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Larapen\LaravelDistance\Libraries\mysql\DistanceHelper;

trait ListingsListTrait
{
	/**
	 * Saved
	 *
	 * @param $setting
	 */
	public function listingsListSaved($setting)
	{
		$this->saveTheDisplayModeInCookie($setting);
		$this->applyDistanceCalculationFunctionOperation($setting);
	}
	
	/**
	 * @param $setting
	 */
	private function applyDistanceCalculationFunctionOperation($setting): void
	{
		// If the 'distance_calculation_formula' has been changed
		if (array_key_exists('distance_calculation_formula', $setting->value)) {
			$this->removeDistanceCalculationFunctionsCache();
			$this->createDistanceCalculationFunction($setting);
		}
	}
	
	/**
	 * If the 'distance_calculation_formula' has been changed,
	 * Remove Distance Calculation Functions from Cache
	 */
	private function removeDistanceCalculationFunctionsCache(): void
	{
		try {
			$customFunctions = ['haversine', 'orthodromy'];
			foreach ($customFunctions as $function) {
				// Drop the function, If exists
				$sql = 'DROP FUNCTION IF EXISTS ' . $function . ';';
				DB::statement($sql);
				
				// Remove the corresponding cache (@todo: remove it)
				$cacheId = 'checkIfMySQLFunctionExists.' . $function;
				if (Cache::has($cacheId)) {
					Cache::forget($cacheId);
				}
			}
		} catch (\Exception $e) {
		}
	}
	
	/**
	 * If the 'distance_calculation_formula' has been changed,
	 * If the selected Distance Calculation Function doesn't exist, then create it
	 *
	 * @param $setting
	 */
	private function createDistanceCalculationFunction($setting): void
	{
		// Create the MySQL Distance Calculation function, If it doesn't exist.
		if (!DistanceHelper::checkIfDistanceCalculationFunctionExists($setting->value['distance_calculation_formula'])) {
			$res = DistanceHelper::createDistanceCalculationFunction($setting->value['distance_calculation_formula']);
		}
	}
	
	/**
	 * Save the new Display Mode in cookie
	 *
	 * @param $setting
	 */
	private function saveTheDisplayModeInCookie($setting): void
	{
		// If the Default List Mode is changed, then clear the 'list_display_mode' from the cookies
		// NOTE: The cookie has been set from JavaScript, so we have to provide the good path (maybe the good expire time)
		if (isset($setting->value['display_mode'])) {
			Cookie::forget('display_mode');
			
			$expire = 60 * 24 * 7; // 7 days
			Cookie::set('display_mode', $setting->value['display_mode'], $expire);
		}
	}
}
