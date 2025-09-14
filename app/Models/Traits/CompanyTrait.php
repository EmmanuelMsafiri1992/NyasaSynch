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

use App\Helpers\Files\Storage\StorageDisk;
use App\Helpers\UrlGen;

trait CompanyTrait
{
	// ===| ADMIN PANEL METHODS |===
	
	public function getNameHtml(): string
	{
		$company = self::find($this->id);
		
		$out = '';
		if (!empty($company)) {
			$out .= '<a href="' . UrlGen::company(null, $company->id) . '" target="_blank">';
			$out .= $company->name;
			$out .= '</a>';
			$out .= ' <span class="label label-default">' . $company->posts()->count() . ' ' . trans('admin.jobs') . '</span>';
		} else {
			$out .= '--';
		}
		
		return $out;
	}
	
	public function getLogoHtml(): string
	{
		$style = ' style="width:auto; max-height:90px;"';
		
		// Get logo
		$out = '<img src="' . imgUrl($this->logo, 'small') . '" data-bs-toggle="tooltip" title="' . $this->name . '"' . $style . '>';
		
		// Add link to the Ad
		$url = UrlGen::company(null, $this->id);
		
		return '<a href="' . $url . '" target="_blank">' . $out . '</a>';
	}
	
	public function getCountryHtml()
	{
		$country = $this->country ?? null;
		$countryCode = $country->code ?? $this->country_code ?? null;
		$countryFlagUrl = $country->flag_url ?? $this->country_flag_url ?? null;
		
		if (!empty($countryFlagUrl)) {
			return '<img src="' . $countryFlagUrl . '" data-bs-toggle="tooltip" title="' . $countryCode . '">';
		} else {
			return $countryCode;
		}
	}
	
	// ===| OTHER METHODS |===
	
	public static function getLogo($value)
	{
		if (empty($value)) {
			return $value;
		}
		
		$disk = StorageDisk::getDisk();
		
		// OLD PATH
		$oldBase = 'pictures/';
		$newBase = 'files/';
		if (str_contains($value, $oldBase)) {
			$value = $newBase . last(explode($oldBase, $value));
		}
		
		// NEW PATH
		if (str_ends_with($value, '/')) {
			return $value;
		}
		
		if (empty($value) || !$disk->exists($value)) {
			$value = config('larapen.media.picture');
		}
		
		return $value;
	}
}
