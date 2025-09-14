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

namespace App\Helpers\Search\Traits;

trait Select
{
	protected function setSelect(): void
	{
		if (!(isset($this->posts) && isset($this->postsTable))) {
			return;
		}
		
		// Default Select Columns
		$select = [
			$this->postsTable . '.id',
			'country_code',
			'user_id',
			'category_id',
			'post_type_id',
			'company_id',
			'company_name',
			'logo',
			'title',
			$this->postsTable . '.description',
			'salary_min',
			'salary_max',
			'salary_type_id',
			'city_id',
			'featured',
			$this->postsTable . '.created_at',
			'email_verified_at',
			'phone_verified_at',
			'reviewed_at',
		];
		if (isFromApi() && !doesRequestIsFromWebApp()) {
			$select[] = $this->postsTable . '.description';
			$select[] = 'contact_name';
			$select[] = $this->postsTable . '.auth_field';
			$select[] = $this->postsTable . '.phone';
			$select[] = $this->postsTable . '.email';
		}
		if (config('settings.listings_list.show_listings_tags')) {
			$select[] = 'tags';
		}
		
		// Default GroupBy Columns
		$groupBy = [$this->postsTable . '.id'];
		
		// Merge Columns
		$this->select = array_merge($this->select, $select);
		$this->groupBy = array_merge($this->groupBy, $groupBy);
		
		// Add the Select Columns
		if (!empty($this->select)) {
			foreach ($this->select as $column) {
				$this->posts->addSelect($column);
			}
		}
		
		// If the MySQL strict mode is activated, ...
		// Append all the non-calculated fields available in the 'SELECT' in 'GROUP BY' to prevent error related to 'only_full_group_by'
		if (self::$dbModeStrict) {
			$this->groupBy = $this->select;
		}
	}
}
