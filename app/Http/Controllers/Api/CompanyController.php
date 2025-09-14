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

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Company\SaveCompany;
use App\Http\Requests\Front\CompanyRequest;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\EntityCollection;
use App\Models\Company;

/**
 * @group Companies
 */
class CompanyController extends BaseController
{
	use SaveCompany;
	
	/**
	 * List companies
	 *
	 * @queryParam hasPosts boolean Do entries have Post(s)? - Possible value: 0 or 1. Example: 0
	 * @queryParam countPosts boolean Count posts number for each entry? - Possible value: 0 or 1. Example: 0
	 * @queryParam belongLoggedUser boolean Force users to be logged to get data that belongs to him - Possible value: 0 or 1. Example: 0
	 * @queryParam q string Get the company list related to the entered keyword. Example: null
	 * @queryParam sort string The sorting parameter (Order by DESC with the given column. Use "-" as prefix to order by ASC). Possible values: created_at, name. Example: created_at
	 * @queryParam perPage int Items per page. Can be defined globally from the admin settings. Cannot be exceeded 100. Example: 2
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function index(): \Illuminate\Http\JsonResponse
	{
		$doEntriesHavePosts = (request()->filled('hasPosts') && request()->integer('hasPosts') == 1);
		$isWithCountPosts = (request()->filled('countPosts') && request()->integer('countPosts') == 1);
		$isBelongLoggedUser = (request()->filled('belongLoggedUser') && request()->integer('belongLoggedUser') == 1);
		$keyword = request()->input('q');
		$perPage = getNumberOfItemsPerPage('companies', request()->integer('perPage'));
		$isListingsReviewEnabled = config('settings.listing_form.listings_review_activation');
		
		$embed = explode(',', request()->input('embed'));
		
		// Non Cached Query
		$companies = Company::query()->with(['user', 'user.permissions', 'user.roles']);
		
		if ($doEntriesHavePosts) {
			$companies->whereHas('posts', function ($query) {
				$query->inCountry()->verified()->unarchived();
				if (config('settings.listing_form.listings_review_activation')) {
					$query->reviewed();
				}
			});
		}
		
		if ($isWithCountPosts) {
			$companies->withCount([
				'posts' => function ($query) use ($isListingsReviewEnabled) {
					$query->inCountry()->verified()->unarchived();
					if ($isListingsReviewEnabled) {
						$query->reviewed();
					}
				},
			]);
		}
		
		// Apply search filter
		if (!empty($keyword)) {
			$keywords = rawurldecode($keyword);
			$companies->where(function ($query) use ($keywords) {
				$query->where('name', 'LIKE', '%' . $keywords . '%')
					->whereOr('description', 'LIKE', '%' . $keywords . '%');
			});
		}
		
		if ($isBelongLoggedUser) {
			$userId = auth('sanctum')->check() ? auth('sanctum')->user()->getAuthIdentifier() : '-1';
			$companies->where('user_id', $userId);
		}
		
		// Sorting
		$companies = $this->applySorting($companies, ['created_at', 'name']);
		
		$companies = $companies->paginate($perPage);
		
		// If the request is made from the app's Web environment,
		// use the Web URL as the pagination's base URL
		$companies = setPaginationBaseUrl($companies);
		
		$collection = new EntityCollection(class_basename($this), $companies);
		
		$message = ($companies->count() <= 0) ? t('no_companies_found') : null;
		
		return apiResponse()->withCollection($collection, $message);
	}
	
	/**
	 * Get company
	 *
	 * @queryParam belongLoggedUser boolean Check if entry is belonged the logged user - Possible value: 0 or 1. Example: 0
	 * @queryParam embed string The Comma-separated list of the company relationships for Eager Loading - Possible values: user,city,subAdmin1,subAdmin2. Example: user
	 *
	 * @urlParam id int required The company's ID. Example: 44
	 *
	 * @param $id
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function show($id): \Illuminate\Http\JsonResponse
	{
		$embed = explode(',', request()->input('embed'));
		
		$company = Company::query();
		
		if (in_array('user', $embed)) {
			$company->with('user');
		}
		if (in_array('city', $embed)) {
			$company->with('city');
			if (in_array('subAdmin1', $embed)) {
				$company->with('city.subAdmin1');
			}
			if (in_array('subAdmin2', $embed)) {
				$company->with('city.subAdmin2');
			}
		}
		
		if (request()->input('belongLoggedUser')) {
			$userId = auth('sanctum')->check() ? auth('sanctum')->user()->getAuthIdentifier() : '-1';
			$company->where('user_id', $userId);
		}
		
		$company = $company->where('id', $id)->first();
		
		if (empty($company)) {
			return apiResponse()->notFound(t('company_not_found'));
		}
		
		$resource = new CompanyResource($company);
		
		return apiResponse()->withResource($resource);
	}
	
	/**
	 * Store company
	 *
	 * @authenticated
	 * @header Authorization Bearer {YOUR_AUTH_TOKEN}
	 *
	 * @bodyParam company[].country_code string required The code of the company's country. Example: US
	 * @bodyParam company[].name string required The company's name. Example: Foo Inc
	 * @bodyParam company[].logo file The company's logo.
	 * @bodyParam company[].description string required The company's description. Example: Nostrum quia est aut quas. Consequuntur ut quis odit voluptatem laborum quia.
	 * @bodyParam company[].city_id int The company city's ID.
	 * @bodyParam company[].address string The company's address. Example: 5 rue de l'Echelle
	 * @bodyParam company[].phone string The company's phone number. Example: +17656766467
	 * @bodyParam company[].fax string The company's fax number. Example: +80159266712
	 * @bodyParam company[].email string The company's email address. Example: contact@domain.tld
	 * @bodyParam company[].website string The company's website URL. Example: https://domain.tld
	 * @bodyParam company[].facebook string The company's Facebook URL.
	 * @bodyParam company[].twitter string The company's Twitter URL.
	 * @bodyParam company[].linkedin string The company's LinkedIn URL.
	 * @bodyParam company[].pinterest string The company's Pinterest URL.
	 *
	 * @param \App\Http\Requests\Front\CompanyRequest $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function store(CompanyRequest $request): \Illuminate\Http\JsonResponse
	{
		$authUser = auth('sanctum')->user();
		if (!isset($authUser->id)) {
			return apiResponse()->notFound(t('user_not_found'));
		}
		
		// Create Company
		$company = $this->storeCompany($authUser->id, $request);
		
		$data = [
			'success' => true,
			'message' => t('Your company has created successfully'),
			'result'  => (new CompanyResource($company))->toArray($request),
		];
		
		return apiResponse()->json($data);
	}
	
	/**
	 * Update company
	 *
	 * @authenticated
	 * @header Authorization Bearer {YOUR_AUTH_TOKEN}
	 *
	 * @bodyParam company[].country_code string required The code of the company's country. Example: US
	 * @bodyParam company[].name string required The company's name. Example: Foo Inc
	 * @bodyParam company[].logo file The company's logo.
	 * @bodyParam company[].description string required The company's description. Example: Nostrum quia est aut quas. Consequuntur ut quis odit voluptatem laborum quia.
	 * @bodyParam company[].city_id int The company city's ID.
	 * @bodyParam company[].address string The company's address. Example: 5 rue de l'Echelle
	 * @bodyParam company[].phone string The company's phone number. Example: +17656766467
	 * @bodyParam company[].fax string The company's fax number. Example: +80159266712
	 * @bodyParam company[].email string The company's email address. Example: contact@domain.tld
	 * @bodyParam company[].website string The company's website URL. Example: https://domain.tld
	 * @bodyParam company[].facebook string The company's Facebook URL.
	 * @bodyParam company[].twitter string The company's Twitter URL.
	 * @bodyParam company[].linkedin string The company's LinkedIn URL.
	 * @bodyParam company[].pinterest string The company's Pinterest URL.
	 *
	 * @urlParam id int required The company's ID. Example: 1
	 *
	 * @param $id
	 * @param \App\Http\Requests\Front\CompanyRequest $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function update($id, CompanyRequest $request): \Illuminate\Http\JsonResponse
	{
		$authUser = auth('sanctum')->user();
		if (!isset($authUser->id)) {
			return apiResponse()->notFound(t('user_not_found'));
		}
		
		$company = Company::where('user_id', $authUser->id)->where('id', $id)->first();
		
		if (empty($company)) {
			return apiResponse()->notFound(t('company_not_found'));
		}
		
		// Update the Company
		$company = $this->updateCompany($authUser->id, $request, $company);
		
		$data = [
			'success' => true,
			'message' => t('Your company details has updated successfully'),
			'result'  => (new CompanyResource($company))->toArray($request),
		];
		
		return apiResponse()->json($data);
	}
	
	/**
	 * Delete company(ies)
	 *
	 * @authenticated
	 * @header Authorization Bearer {YOUR_AUTH_TOKEN}
	 *
	 * @urlParam ids string required The ID or comma-separated IDs list of company(ies).
	 *
	 * @param string $ids
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function destroy(string $ids): \Illuminate\Http\JsonResponse
	{
		$authUser = auth('sanctum')->user();
		if (!isset($authUser->id)) {
			return apiResponse()->notFound(t('user_not_found'));
		}
		
		$data = [
			'success' => false,
			'message' => t('no_deletion_is_done'),
			'result'  => null,
		];
		
		// Get Entries ID (IDs separated by comma accepted)
		$ids = explode(',', $ids);
		
		// Delete
		$res = false;
		foreach ($ids as $companyId) {
			$company = Company::query()
				->where('user_id', $authUser->id)
				->where('id', $companyId)
				->first();
			
			if (!empty($company)) {
				$res = $company->delete();
			}
		}
		
		// Confirmation
		if ($res) {
			$data['success'] = true;
			
			$count = count($ids);
			if ($count > 1) {
				$data['message'] = t('x entities has been deleted successfully', ['entities' => t('companies'), 'count' => $count]);
			} else {
				$data['message'] = t('1 entity has been deleted successfully', ['entity' => t('company')]);
			}
		}
		
		return apiResponse()->json($data);
	}
}
