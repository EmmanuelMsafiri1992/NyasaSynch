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

namespace App\Http\Controllers\Web\Public\Post\CreateOrEdit\Traits;

trait CategoriesTrait
{
	/**
	 * @param int|null $catId
	 * @param string|null $languageCode
	 * @return array|null
	 */
	private function getCategoryById(?int $catId, ?string $languageCode = null): ?array
	{
		if (empty($catId)) return null;
		
		// Get categories - Call API endpoint
		$cacheId = 'api.categories.show.' . $catId . '.' . $languageCode;
		$apiResult = cache()->remember($cacheId, $this->cacheExpiration, function () use ($catId, $languageCode) {
			$endpoint = '/categories/' . $catId;
			$queryParams = [
				'embed'           => 'children,parent',
				'language_code'   => $languageCode ?? config('app.locale'),
				'cacheExpiration' => $this->cacheExpiration,
			];
			$queryParams = array_merge(request()->all(), $queryParams);
			$data = makeApiRequest('get', $endpoint, $queryParams);
			
			$apiMessage = $this->handleHttpError($data);
			
			return data_get($data, 'result');
		});
		
		return is_array($apiResult) ? $apiResult : null;
	}
	
	/**
	 * @param int|null $catId
	 * @param string|null $languageCode
	 * @param string|null $apiMessage
	 * @param int|null $page
	 * @return array
	 */
	private function getCategories(
		?int    $catId = null,
		?string $languageCode = null,
		?string &$apiMessage = null,
		?int    $page = null
	): array
	{
		$catId = $catId ?? 0;
		$perPage = getNumberOfItemsPerPage('categories');
		
		// Get categories - Call API endpoint
		$cacheId = 'api.categories.list.' . $catId . '.take.' . $perPage . '.' . $languageCode . '.page.' . $page;
		$apiResult = cache()->remember($cacheId, $this->cacheExpiration, function () use (
			$perPage, $catId, $languageCode, $page
		) {
			$endpoint = '/categories';
			$queryParams = [
				'parentId'        => $catId,
				'nestedIncluded'  => false,
				'embed'           => 'children,parent',
				'sort'            => '-lft',
				'language_code'   => $languageCode ?? config('app.locale'),
				'cacheExpiration' => $this->cacheExpiration,
				'perPage'         => $perPage,
			];
			if (!empty($page)) {
				$queryParams['page'] = $page;
			}
			$queryParams = array_merge(request()->all(), $queryParams);
			$headers = [
				'X-WEB-REQUEST-URL' => request()->fullUrlWithQuery(['catId' => $catId]),
			];
			$categoriesData = makeApiRequest('get', $endpoint, $queryParams, $headers);
			
			$apiMessage = $this->handleHttpError($categoriesData);
			
			return data_get($categoriesData, 'result');
		});
		
		return is_array($apiResult) ? $apiResult : [];
	}
	
	/**
	 * Format Categories
	 *
	 * If catId is null, get list of categories
	 * If catId is not null, get the selected category's list of subcategories
	 *
	 * @param array|null $categories
	 * @param int|null $catId
	 * @return \Illuminate\Support\Collection
	 */
	private function formatCategories(?array $categories, ?int $catId = null): \Illuminate\Support\Collection
	{
		$categories = collect($categories);
		
		if ($categories->count() > 0) {
			if (in_array($this->catDisplayType, $this->catsWithPictureTypes)) {
				$categories = $categories->keyBy('id');
			} else {
				$numberOfCols = 3; // Number of columns
				$maxRowsPerCol = ceil($categories->count() / $numberOfCols);
				$maxRowsPerCol = ($maxRowsPerCol > 0) ? $maxRowsPerCol : 1; // Fix array_chunk with 0
				
				$categories = $categories->chunk($maxRowsPerCol);
			}
		}
		
		return $categories;
	}
}
