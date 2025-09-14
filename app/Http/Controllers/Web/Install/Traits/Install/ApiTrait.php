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

namespace App\Http\Controllers\Web\Install\Traits\Install;

use App\Helpers\Cookie;
use App\Helpers\GeoIP;

trait ApiTrait
{
	/**
	 * Get the user's country code with his IP address
	 *
	 * @param array|null $defaultDrivers
	 * @return string|null
	 */
	private static function getCountryCodeFromIPAddr(?array $defaultDrivers = ['ipapi', 'ipapico']): ?string
	{
		if (empty($defaultDrivers)) {
			return null;
		}
		
		$countryCode = Cookie::get('ipCountryCode');
		if (empty($countryCode)) {
			// Localize the user's country
			try {
				foreach ($defaultDrivers as $driver) {
					config()->set('geoip.default', $driver);
					
					$data = (new GeoIP())->getData();
					$countryCode = data_get($data, 'countryCode');
					
					// Fix for some countries
					$countryCode = ($countryCode == 'UK') ? 'GB' : $countryCode;
					
					if (!is_string($countryCode) || strlen($countryCode) != 2) {
						// Remove the current element (driver) from the array
						$currDefaultDrivers = array_diff($defaultDrivers, [$driver]);
						if (!empty($currDefaultDrivers)) {
							return self::getCountryCodeFromIPAddr($currDefaultDrivers);
						}
						
						return null;
					} else {
						break;
					}
				}
			} catch (\Throwable $t) {
				return null;
			}
			
			// Set data in cookie
			Cookie::set('ipCountryCode', $countryCode);
		}
		
		return getAsStringOrNull($countryCode);
	}
}
