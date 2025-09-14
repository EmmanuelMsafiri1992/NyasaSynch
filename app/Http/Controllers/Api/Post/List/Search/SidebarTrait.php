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

namespace App\Http\Controllers\Api\Post\List\Search;

use App\Helpers\Date;
use App\Models\Category;
use App\Models\City;
use App\Models\PostType;
use Larapen\LaravelDistance\Libraries\mysql\DistanceHelper;

trait SidebarTrait
{
	/**
	 * @param array|null $preSearch
	 * @return array
	 */
	public function getSidebar(?array $preSearch = []): array
	{
		$citiesLimit = getNumberOfItemsToTake('cities');
		
		$data = [];
		
		// Get Root Categories
		$data['cats'] = $this->getRootCategories();
		
		$data['cat'] = $preSearch['cat'] ?? null;
		
		$data['city'] = $preSearch['city'] ?? null;
		$data['admin'] = $preSearch['admin'] ?? null;
		
		if ($data['city'] instanceof City) {
			$data['city'] = $data['city']->toArray();
		}
		
		$data['countPostsPerCat'] = $this->countPostsPerCategory($data['city']);
		$data['cities'] = $this->getMostPopulateCities($citiesLimit);
		$data['periodList'] = $this->getPeriodList();
		$data['postTypes'] = $this->getPostTypes();
		$data['orderByOptions'] = $this->orderByOptions($data['city']);
		$data['displayModes'] = $this->getDisplayModes();
		
		return $data;
	}
	
	/**
	 * @param array|null $city
	 * @return array
	 */
	private function countPostsPerCategory(?array $city = []): array
	{
		if (!config('settings.listings_list.count_categories_listings')) {
			return [];
		}
		
		if (!empty($city) && !empty(data_get($city, 'id'))) {
			$cityId = data_get($city, 'id');
			$cacheId = config('country.code') . '.' . $cityId . '.count.posts.per.cat.' . config('app.locale');
			$countPostsPerCat = cache()->remember($cacheId, $this->cacheExpiration, function () use ($cityId) {
				return Category::countPostsPerCategory($cityId);
			});
		} else {
			$cacheId = config('country.code') . '.count.posts.per.cat.' . config('app.locale');
			$countPostsPerCat = cache()->remember($cacheId, $this->cacheExpiration, function () {
				return Category::countPostsPerCategory();
			});
		}
		
		return $countPostsPerCat;
	}
	
	/**
	 * @param int $limit
	 * @return array
	 */
	private function getMostPopulateCities(int $limit = 50): array
	{
		if (config('settings.listings_list.count_cities_listings')) {
			$cacheId = config('country.code') . '.cities.withCountPosts.take.' . $limit;
			$cities = cache()->remember($cacheId, $this->cacheExpiration, function () use ($limit) {
				return City::inCountry()->withCount('posts')->take($limit)->orderByDesc('population')->orderBy('name')->get();
			});
		} else {
			$cacheId = config('country.code') . '.cities.take.' . $limit;
			$cities = cache()->remember($cacheId, $this->cacheExpiration, function () use ($limit) {
				return City::inCountry()->take($limit)->orderByDesc('population')->orderBy('name')->get();
			});
		}
		
		return $cities->toArray();
	}
	
	/**
	 * @return array|string[]
	 */
	private function getPeriodList(): array
	{
		$tz = Date::getAppTimeZone();
		
		return [
			// '2'   => now($tz)->subDays()->fromNow(),
			'4'   => now($tz)->subDays(3)->fromNow(),
			'8'   => now($tz)->subDays(7)->fromNow(),
			'31'  => now($tz)->subMonths()->fromNow(),
			// '92'  => now($tz)->subMonths(3)->fromNow(),
			'184' => now($tz)->subMonths(6)->fromNow(),
			'368' => now($tz)->subYears()->fromNow(),
		];
	}
	
	/**
	 * @return array
	 */
	private function getPostTypes(): array
	{
		$cacheId = 'postTypes.all.' . config('app.locale');
		$postTypes = cache()->remember($cacheId, $this->cacheExpiration, function () {
			return PostType::orderBy('lft')->get();
		});
		
		if ($postTypes->count() > 0) {
			$postTypes = $postTypes->keyBy('id');
		}
		
		return $postTypes->toArray();
	}
	
	/**
	 * @param array|null $city
	 * @return array
	 */
	private function orderByOptions(?array $city = []): array
	{
		$distanceRange = $this->getDistanceRanges($city);
		
		$orderByArray = [
			[
				'condition'  => true,
				'isSelected' => false,
				'query'      => ['orderBy' => 'distance'],
				'label'      => t('Sort by'),
			],
			[
				'condition'  => true,
				'isSelected' => (request()->input('orderBy') == 'salaryAsc'),
				'query'      => ['orderBy' => 'salaryAsc'],
				'label'      => t('salary_low_to_high'),
			],
			[
				'condition'  => true,
				'isSelected' => (request()->input('orderBy') == 'salaryDesc'),
				'query'      => ['orderBy' => 'salaryDesc'],
				'label'      => t('salary_high_to_low'),
			],
			[
				'condition'  => request()->filled('q'),
				'isSelected' => (request()->input('orderBy') == 'relevance'),
				'query'      => ['orderBy' => 'relevance'],
				'label'      => t('Relevance'),
			],
			[
				'condition'  => true,
				'isSelected' => (request()->input('orderBy') == 'date'),
				'query'      => ['orderBy' => 'date'],
				'label'      => t('Date'),
			],
			[
				'condition'  => config('plugins.reviews.installed'),
				'isSelected' => (request()->input('orderBy') == 'rating'),
				'query'      => ['orderBy' => 'rating'],
				'label'      => trans('reviews::messages.Rating'),
			],
		];
		
		return array_merge($orderByArray, $distanceRange);
	}
	
	/**
	 * @param array|null $city
	 * @return array
	 */
	private function getDistanceRanges(?array $city = []): array
	{
		if (!config('settings.listings_list.cities_extended_searches')) {
			return [];
		}
		
		config()->set('distance.distanceRange.min', 0);
		config()->set('distance.distanceRange.max', config('settings.listings_list.search_distance_max', 500));
		config()->set('distance.distanceRange.interval', config('settings.listings_list.search_distance_interval', 150));
		$distanceRange = DistanceHelper::distanceRange();
		
		// Format the Array for the OrderBy SelectBox
		$defaultDistance = config('settings.listings_list.search_distance_default', 100);
		
		return collect($distanceRange)->mapWithKeys(function ($item, $key) use ($defaultDistance, $city) {
			return [
				$key => [
					'condition'  => !empty($city),
					'isSelected' => (request()->input('distance', $defaultDistance) == $item),
					'query'      => ['distance' => $item],
					'label'      => t('around_x_distance', ['distance' => $item, 'unit' => getDistanceUnit()]),
				],
			];
		})->toArray();
	}
	
	/**
	 * @return array[]
	 */
	private function getDisplayModes(): array
	{
		return [
			'make-grid'    => [
				'icon'  => 'bi bi-grid-fill',
				'query' => ['display' => 'grid'],
			],
			'make-list'    => [
				'icon'  => 'fa-solid fa-list',
				'query' => ['display' => 'list'],
			],
			'make-compact' => [
				'icon'  => 'fa-solid fa-bars',
				'query' => ['display' => 'compact'],
			],
		];
	}
}
