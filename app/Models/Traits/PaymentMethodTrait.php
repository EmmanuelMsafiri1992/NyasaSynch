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

namespace App\Models\Traits;

trait PaymentMethodTrait
{
	// ===| ADMIN PANEL METHODS |===
	
	public function getDisplayNameHtml(): string
	{
		$out = $this->display_name ?? '--';
		if (!empty($this->name)) {
			$out = $out . plugin_demo_info($this->name);
		}
		
		return $out;
	}
	
	public function getCountriesHtml(): string
	{
		$out = strtoupper(trans('admin.All'));
		if (!empty($this->countries)) {
			$countriesCropped = str($this->countries)->limit(50, ' [...]');
			$out = '<div title="' . $this->countries . '">' . $countriesCropped . '</div>';
		}
		
		return $out;
	}
	
	// ===| OTHER METHODS |===
}
