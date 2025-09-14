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

namespace App\Enums;

enum Continent: string
{
	use EnumToArray;
	
	case AFRICA = 'AF';
	case ANTARCTICA = 'AN';
	case ASIA = 'AS';
	case EUROPE = 'EU';
	case NORTH_AMERICA = 'NA';
	case OCEANIA = 'OC';
	case SOUTH_AMERICA = 'SA';
	
	public function label(): string
	{
		return match ($this) {
			self::AFRICA => trans('enum.africa'),
			self::ANTARCTICA => trans('enum.antarctica'),
			self::ASIA => trans('enum.asia'),
			self::EUROPE => trans('enum.europe'),
			self::NORTH_AMERICA => trans('enum.north_america'),
			self::OCEANIA => trans('enum.oceania'),
			self::SOUTH_AMERICA => trans('enum.south_america'),
		};
	}
	
	/**
	 * @param $value
	 * @return array
	 */
	public static function find($value = null): array
	{
		if (empty($value)) return [];
		
		$item = self::tryFrom($value);
		if (empty($item)) return [];
		
		return [
			'code'  => $item->value,
			'name'  => $item->name,
			'label' => $item->label(),
		];
	}
}
