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

enum Gender: int
{
	use EnumToArray;
	
	case MALE = 1;
	case FEMALE = 2;
	// case OTHER = 3;
	
	public function label(): string
	{
		return match ($this) {
			self::MALE => trans('enum.male'),
			self::FEMALE => trans('enum.female'),
			// self::OTHER => trans('enum.other'),
		};
	}
	
	public function title(): string
	{
		return match ($this) {
			self::MALE => trans('enum.mr'),
			self::FEMALE => trans('enum.mrs'),
			// self::OTHER => trans('enum.mx'), // In English, the most common gender-neutral title is "Mx." (most often pronounced "miks").
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
			'id'    => $item->value,
			'name'  => $item->name,
			'label' => $item->label(),
			'title' => $item->title(),
		];
	}
}
