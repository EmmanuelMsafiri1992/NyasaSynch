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

namespace App\Http\Controllers\Web\Public\Ajax;

use App\Http\Controllers\Web\Public\Post\CreateOrEdit\Traits\CategoriesTrait;
use App\Http\Controllers\Web\Public\FrontController;
use Illuminate\Http\Request;

class CategoryController extends FrontController
{
	use CategoriesTrait;
	
	protected array $catsWithPictureTypes = ['c_picture_list', 'c_bigIcon_list'];
	protected string $catDisplayType = 'c_border_list';
	
	/**
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getCategoriesHtml(Request $request): \Illuminate\Http\JsonResponse
	{
		$languageCode = $request->input('languageCode', config('app.locale'));
		$selectedCatId = $request->input('selectedCatId');
		$catId = $request->input('catId');
		$catId = !empty($catId) ? $catId : null; // Change 0 to null
		$page = $request->integer('page');
		
		// Update global vars
		$this->catDisplayType = config('settings.listing_form.cat_display_type', 'c_border_list');
		
		// Get category by ID - Call API endpoint
		$category = $this->getCategoryById($catId, $languageCode);
		
		// Get categories - Call API endpoint
		$apiMessage = null;
		$apiResult = $this->getCategories($catId, $languageCode, $apiMessage, $page);
		
		// Get categories list and format it
		$categories = data_get($apiResult, 'data', []);
		$categories = $this->formatCategories($categories, $catId);
		
		$hasChildren = (
			empty($catId)
			|| (!empty($category) && !empty($category['children']))
		);
		
		$data = [
			'apiResult'      => $apiResult,
			'apiMessage'     => $apiMessage,
			'catDisplayType' => $this->catDisplayType,
			'categories'     => $categories, // Adjacent Categories (Children)
			'category'       => $category,
			'hasChildren'    => $hasChildren,
			'catId'          => $selectedCatId,
		];
		
		// Get categories list buffer
		$html = getViewContent('post.createOrEdit.inc.category.select', $data);
		
		// Send JSON Response
		$result = [
			'html'        => $html,
			'category'    => $category,
			'hasChildren' => $hasChildren,
			'parent'      => $category['parent'] ?? null,
		];
		
		return ajaxResponse()->json($result);
	}
}
