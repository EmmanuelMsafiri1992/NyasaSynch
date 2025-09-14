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

namespace App\Models\Builders;

use App\Models\Builders\Classes\TranslationsBuilder;

trait HasTranslationsBuilder
{
	/**
	 * Get a new query builder instance for the connection for translatable models
	 * This overwrites the custom global builder in the 'HasGlobalBuilder.php' file
	 *
	 * @param $query
	 * @return \App\Models\Builders\Classes\TranslationsBuilder
	 */
	public function newEloquentBuilder($query)
	{
		return new TranslationsBuilder($query);
	}
}
