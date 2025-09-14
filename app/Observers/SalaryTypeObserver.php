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

namespace App\Observers;

use App\Models\SalaryType;

class SalaryTypeObserver
{
	/**
	 * Listen to the Entry saved event.
	 *
	 * @param SalaryType $salaryType
	 * @return void
	 */
	public function saved(SalaryType $salaryType)
	{
		// Removing Entries from the Cache
		$this->clearCache($salaryType);
	}
	
	/**
	 * Listen to the Entry deleted event.
	 *
	 * @param SalaryType $salaryType
	 * @return void
	 */
	public function deleted(SalaryType $salaryType)
	{
		// Removing Entries from the Cache
		$this->clearCache($salaryType);
	}
	
	/**
	 * Removing the Entity's Entries from the Cache
	 *
	 * @param $salaryType
	 * @return void
	 */
	private function clearCache($salaryType): void
	{
		try {
			cache()->flush();
		} catch (\Exception $e) {
		}
	}
}
