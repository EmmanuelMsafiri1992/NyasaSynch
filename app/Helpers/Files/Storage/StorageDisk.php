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

namespace App\Helpers\Files\Storage;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

class StorageDisk
{
	/**
	 * Get the default disk name
	 *
	 * @return string|null
	 */
	public static function getDiskName(): ?string
	{
		$defaultDisk = config('filesystems.default', 'public');
		
		// $defaultDisk = config('filesystems.cloud'); // Only for tests purpose!
		
		return getAsStringOrNull($defaultDisk);
	}
	
	/**
	 * Get the default disk resources
	 *
	 * @param string|null $name
	 * @return \Illuminate\Contracts\Filesystem\Filesystem
	 */
	public static function getDisk(string $name = null): Filesystem
	{
		$defaultDisk = !is_null($name) ? $name : self::getDiskName();
		
		return Storage::disk($defaultDisk);
	}
}
