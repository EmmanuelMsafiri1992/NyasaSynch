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

namespace App\Http\Controllers\Api\Post\List;

use App\Helpers\Search\PostQueries;
use App\Http\Controllers\Api\Post\List\Search\CategoryTrait;
use App\Http\Controllers\Api\Post\List\Search\LocationTrait;
use App\Http\Controllers\Api\Post\List\Search\SidebarTrait;
use Larapen\LaravelDistance\Libraries\mysql\DistanceHelper;

trait SearchTrait
{
	use CategoryTrait, LocationTrait, SidebarTrait;
	
	/**
	 * @param string $op
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getPostsBySearch(string $op): \Illuminate\Http\JsonResponse
	{
		// Create the MySQL Distance Calculation function If it doesn't exist
		$distanceCalculationFormula = config('settings.listings_list.distance_calculation_formula', 'haversine');
		if (!DistanceHelper::checkIfDistanceCalculationFunctionExists($distanceCalculationFormula)) {
			DistanceHelper::createDistanceCalculationFunction($distanceCalculationFormula);
		}
		
		$preSearch = [];
		
		// $embed = ['user', 'category', 'parent', 'postType', 'city', 'savedByLoggedUser', 'pictures', 'payment', 'package', 'company'];
		$embed = ['user', 'savedByLoggedUser', 'pictures', 'payment', 'package', 'company'];
		if (!config('settings.listings_list.hide_post_type')) {
			$embed[] = 'postType';
		}
		if (!config('settings.listings_list.hide_category')) {
			$embed[] = 'category';
			$embed[] = 'parent';
		}
		if (!config('settings.listings_list.hide_location')) {
			$embed[] = 'city';
		}
		request()->query->add(['embed' => implode(',', $embed)]);
		
		$perPage = getNumberOfItemsPerPage('posts', request()->integer('perPage'));
		
		$orderBy = request()->input('orderBy');
		$orderBy = ($orderBy != 'random') ? $orderBy : null;
		
		$input = [
			'op'      => $op,
			'perPage' => $perPage,
			'orderBy' => $orderBy,
		];
		
		$searchData = $this->searchPosts($input, $preSearch);
		$preSearch = $searchData['preSearch'] ?? $preSearch;
		
		$data = [
			'success' => true,
			'message' => $searchData['message'] ?? null,
			'result'  => $searchData['posts'],
			'extra'   => [
				'count'     => $searchData['count'] ?? [],
				'preSearch' => $preSearch,
				'sidebar'   => $this->getSidebar($preSearch),
				'tags'      => $searchData['tags'] ?? [],
			],
		];
		
		return apiResponse()->json($data);
	}
	
	/**
	 * @param $input
	 * @param $preSearch
	 * @return array
	 */
	protected function searchPosts($input, &$preSearch): array
	{
		$location = $this->getLocation();
		
		$preSearch = [
			'cat'   => $this->getCategory(),
			'city'  => $location['city'] ?? null,
			'admin' => $location['admin'] ?? null,
		];
		
		$queriesToRemove = ['op', 'embed'];
		
		return (new PostQueries($input, $preSearch))->fetch($queriesToRemove);
	}
}
