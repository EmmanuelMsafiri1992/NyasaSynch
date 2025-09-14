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

use App\Http\Controllers\Api\Resume\SaveResume;
use App\Http\Requests\Front\ResumeRequest;
use App\Http\Resources\EntityCollection;
use App\Http\Resources\ResumeResource;
use App\Models\Resume;

/**
 * @group Resumes
 */
class ResumeController extends BaseController
{
	use SaveResume;
	
	/**
	 * List resumes
	 *
	 * @queryParam q string Get the résumé list related to the entered keyword. Example: null
	 * @queryParam belongLoggedUser boolean Force users to be logged to get data that belongs to him. Resume file and other column can be retrieved - Possible value: 0 or 1. Example: 0
	 * @queryParam sort string The sorting parameter (Order by DESC with the given column. Use "-" as prefix to order by ASC). Possible values: created_at, name. Example: created_at
	 * @queryParam perPage int Items per page. Can be defined globally from the admin settings. Cannot be exceeded 100. Example: 2
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function index(): \Illuminate\Http\JsonResponse
	{
		$isBelongLoggedUser = (request()->filled('belongLoggedUser') && request()->integer('belongLoggedUser') == 1);
		$isForApplyingJob = (request()->filled('forApplyingJob') && request()->integer('forApplyingJob') == 1);
		$perPage = getNumberOfItemsPerPage('resumes', request()->integer('perPage'));
		
		$resumes = Resume::query();
		
		// Apply search filter
		if (request()->filled('q')) {
			$keywords = rawurldecode(request()->input('q'));
			$resumes->where('name', 'LIKE', '%' . $keywords . '%');
		}
		
		if ($isBelongLoggedUser) {
			$authUser = auth('sanctum')->user();
			$userId = !empty($authUser) ? $authUser->getAuthIdentifier() : '-1';
			$resumes->where('user_id', $userId);
			
			if ($isForApplyingJob) {
				$limit = config('larapen.core.selectResumeInto', 5);
				$resumes->take($limit * 5);
			}
		}
		
		// Sorting
		$resumes = $this->applySorting($resumes, ['created_at', 'name']);
		
		$resumes = $resumes->paginate($perPage);
		
		// If the request is made from the app's Web environment,
		// use the Web URL as the pagination's base URL
		$resumes = setPaginationBaseUrl($resumes);
		
		$collection = new EntityCollection(class_basename($this), $resumes);
		
		$message = ($resumes->count() <= 0) ? t('no_resumes_found') : null;
		
		return apiResponse()->withCollection($collection, $message);
	}
	
	/**
	 * Get resume
	 *
	 * @authenticated
	 * @header Authorization Bearer {YOUR_AUTH_TOKEN}
	 *
	 * @queryParam belongLoggedUser boolean Force users to be logged to get data that belongs to him - Possible value: 0 or 1. Example: 0
	 * @queryParam embed string The Comma-separated list of the company relationships for Eager Loading - Possible values: user. Example: user
	 *
	 * @urlParam id int required The résumé's ID. Example: 269
	 *
	 * @param $id
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function show($id): \Illuminate\Http\JsonResponse
	{
		$embed = explode(',', request()->input('embed'));
		
		$resume = Resume::query()->where('id', $id);
		
		if (request()->input('belongLoggedUser')) {
			$userId = auth('sanctum')->check() ? auth('sanctum')->user()->getAuthIdentifier() : '-1';
			$resume->where('user_id', $userId);
		}
		
		if (in_array('user', $embed)) {
			$resume->with('user');
		}
		
		$resume = $resume->first();
		
		if (empty($resume)) {
			return apiResponse()->notFound(t('resume_not_found'));
		}
		
		$resource = new ResumeResource($resume);
		
		return apiResponse()->withResource($resource);
	}
	
	/**
	 * Store resume
	 *
	 * @authenticated
	 * @header Authorization Bearer {YOUR_AUTH_TOKEN}
	 *
	 * @bodyParam resume[].country_code string required The code of the user's country. Example: US
	 * @bodyParam resume[].name string The résumé's name. Example: Software Engineer
	 * @bodyParam resume[].filename file required The résumé's attached file.
	 *
	 * @param \App\Http\Requests\Front\ResumeRequest $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function store(ResumeRequest $request): \Illuminate\Http\JsonResponse
	{
		$authUser = auth('sanctum')->user();
		if (!isset($authUser->id)) {
			return apiResponse()->notFound(t('user_not_found'));
		}
		
		$resume = $this->storeResume($authUser->id, $request);
		
		$data = [
			'success' => true,
			'message' => t('Your resume has created successfully'),
			'result'  => (new ResumeResource($resume))->toArray($request),
		];
		
		return apiResponse()->json($data);
	}
	
	/**
	 * Update resume
	 *
	 * @authenticated
	 * @header Authorization Bearer {YOUR_AUTH_TOKEN}
	 *
	 * @bodyParam resume[].name string The résumé's name. Example: Software Engineer
	 * @bodyParam resume[].filename file required The résumé's attached file.
	 *
	 * @urlParam id int required The résumé's ID. Example: 1
	 *
	 * @param $id
	 * @param \App\Http\Requests\Front\ResumeRequest $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function update($id, ResumeRequest $request): \Illuminate\Http\JsonResponse
	{
		$authUser = auth('sanctum')->user();
		if (!isset($authUser->id)) {
			return apiResponse()->notFound(t('user_not_found'));
		}
		
		$resume = Resume::where('user_id', $authUser->id)->where('id', $id)->first();
		
		if (empty($resume)) {
			return apiResponse()->notFound(t('resume_not_found'));
		}
		
		$resume = $this->updateResume($authUser->id, $request, $resume);
		
		$data = [
			'success' => true,
			'message' => t('Your resume has updated successfully'),
			'result'  => (new ResumeResource($resume))->toArray($request),
		];
		
		return apiResponse()->json($data);
	}
	
	/**
	 * Delete resume(s)
	 *
	 * @authenticated
	 * @header Authorization Bearer {YOUR_AUTH_TOKEN}
	 *
	 * @urlParam ids string required The ID or comma-separated IDs list of resume(s).
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
		foreach ($ids as $resumeId) {
			$resume = Resume::query()
				->where('user_id', $authUser->id)
				->where('id', $resumeId)
				->first();
			
			if (!empty($resume)) {
				$res = $resume->delete();
			}
		}
		
		// Confirmation
		if ($res) {
			$data['success'] = true;
			
			$count = count($ids);
			if ($count > 1) {
				$data['message'] = t('x entities has been deleted successfully', ['entities' => t('resumes'), 'count' => $count]);
			} else {
				$data['message'] = t('1 entity has been deleted successfully', ['entity' => t('resume')]);
			}
		}
		
		return apiResponse()->json($data);
	}
}
