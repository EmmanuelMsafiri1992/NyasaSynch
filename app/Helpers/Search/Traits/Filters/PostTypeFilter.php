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

namespace App\Helpers\Search\Traits\Filters;

trait PostTypeFilter
{
	protected function applyPostTypeFilter(): void
	{
		if (!isset($this->posts)) {
			return;
		}
		
		$postTypeIds = request()->input('type', []);
		
		if (empty($postTypeIds)) {
			return;
		}
		
		if (is_array($postTypeIds)) {
			$this->posts->whereIn('post_type_id', $postTypeIds);
		}
		
		// Optional
		if (is_numeric($postTypeIds)) {
			$this->posts->where('post_type_id', $postTypeIds);
		}
	}
}
