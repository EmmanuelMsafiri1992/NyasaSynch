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

namespace App\Models\Post;

use App\Models\Post;
use Illuminate\Support\Facades\DB;
use Larapen\LaravelDistance\Distance;

trait SimilarByLocation
{
	/**
	 * Get Posts in the same Location
	 *
	 * @param $distance
	 * @param int|null $limit
	 * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
	 */
	public function getSimilarByLocation($distance, ?int $limit = 20)
	{
		$posts = Post::query();
		
		$tablesPrefix = DB::getTablePrefix();
		$postsTable = (new Post())->getTable();
		
		if (!is_numeric($distance) || $distance < 0) {
			$distance = 0;
		}
		
		$select = [
			$postsTable . '.id',
			$postsTable . '.country_code',
			'category_id',
			'post_type_id',
			'company_id',
			'company_name',
			'logo',
			'title',
			$postsTable . '.description',
			'salary_min',
			'salary_max',
			'salary_type_id',
			'city_id',
			'featured',
			'email_verified_at',
			'phone_verified_at',
			'reviewed_at',
			$postsTable . '.created_at',
			$postsTable . '.archived_at',
		];
		if (isFromApi() && !doesRequestIsFromWebApp()) {
			$select[] = $postsTable . '.description';
			$select[] = 'user_id';
			$select[] = 'contact_name';
			$select[] = $postsTable . '.auth_field';
			$select[] = $postsTable . '.phone';
			$select[] = $postsTable . '.email';
		}
		
		$having = [];
		$orderBy = [];
		
		if (!empty($select)) {
			foreach ($select as $column) {
				$posts->addSelect($column);
			}
		}
		
		// Default Filters
		$posts->inCountry()->verified()->unarchived();
		if (config('settings.listing_form.listings_review_activation')) {
			$posts->reviewed();
		}
		
		// Use the Cities Extended Searches
		config()->set('distance.functions.default', config('settings.listings_list.distance_calculation_formula'));
		config()->set('distance.countryCode', config('country.code'));
		
		if (!empty($this->city)) {
			if (config('settings.listings_list.cities_extended_searches')) {
				
				// Use the Cities Extended Searches
				config()->set('distance.functions.default', config('settings.listings_list.distance_calculation_formula'));
				config()->set('distance.countryCode', config('country.code'));
				
				$sql = Distance::select('lon', 'lat', $this->city->longitude, $this->city->latitude);
				if ($sql) {
					$posts->addSelect(DB::raw($sql));
					$having[] = Distance::having($distance);
					$orderBy[] = Distance::orderBy('ASC');
				} else {
					$posts->where('city_id', $this->city->id);
				}
				
			} else {
				
				// Use the Cities Standard Searches
				$posts->where('city_id', $this->city->id);
				
			}
		}
		
		// Relations
		$posts->has('postType');
		if (!config('settings.listings_list.hide_post_type')) {
			$posts->with('postType');
		}
		$posts->has('category');
		if (!config('settings.listings_list.hide_category')) {
			$posts->with('category', fn ($query) => $query->with('parent'));
		}
		$posts->has('salaryType');
		if (!config('settings.listings_list.hide_salary')) {
			$posts->with('salaryType');
		}
		$posts->has('city');
		if (!config('settings.listings_list.hide_location')) {
			$posts->with('city');
		}
		$posts->with('savedByLoggedUser');
		$posts->with('payment', fn($query) => $query->with('package'));
		$posts->with('user');
		$posts->with('user.permissions');
		
		if (isset($this->id)) {
			$posts->where($postsTable . '.id', '!=', $this->id);
		}
		
		// Set HAVING
		$havingStr = '';
		if (is_array($having) && count($having) > 0) {
			foreach ($having as $key => $value) {
				if (trim($value) == '') {
					continue;
				}
				if (str_contains($value, '.')) {
					$value = $tablesPrefix . $value;
				}
				
				if ($havingStr == '') {
					$havingStr .= $value;
				} else {
					$havingStr .= ' AND ' . $value;
				}
			}
			if (!empty($havingStr)) {
				$posts->havingRaw($havingStr);
			}
		}
		
		// Set ORDER BY
		// $orderBy[] = $tablesPrefix . $postsTable . '.created_at DESC';
		// $posts->orderByRaw(implode(', ', $orderBy));
		$seed = rand(1, 9999);
		$posts->inRandomOrder($seed);
		
		// return $posts->take((int)$limit)->get();
		return $posts->paginate((int)$limit);
	}
}
