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

namespace App\Models\Traits\Common;

use Illuminate\Database\Eloquent\Builder;

trait AppendsTrait
{
	/**
	 * @var bool
	 */
	public static bool $withoutAppends = false;
	
	/**
	 * @var array
	 */
	public static array $withAppends = [];
	
	/*
	|--------------------------------------------------------------------------
	| FUNCTIONS
	|--------------------------------------------------------------------------
	*/
	protected function getArrayableAppends(): array
	{
		if (self::$withoutAppends) {
			return [];
		} else {
			if (!empty(self::$withAppends)) {
				return self::$withAppends;
			}
		}
		
		return parent::getArrayableAppends();
	}
	
	/*
	|--------------------------------------------------------------------------
	| SCOPES
	|--------------------------------------------------------------------------
	*/
	public function scopeWithoutAppends(Builder $builder): Builder
	{
		self::$withoutAppends = true;
		
		return $builder;
	}
	
	public function scopeWithAppends(Builder $builder, array $withAppends = []): Builder
	{
		self::$withAppends = $withAppends;
		
		return $builder;
	}
}
