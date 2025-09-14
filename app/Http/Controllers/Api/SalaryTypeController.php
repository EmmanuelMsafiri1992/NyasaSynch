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

use App\Http\Resources\EntityCollection;
use App\Http\Resources\SalaryTypeResource;
use App\Models\SalaryType;

/**
 * @group Posts
 */
class SalaryTypeController extends BaseController
{
	/**
	 * List salary types
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function index(): \Illuminate\Http\JsonResponse
	{
		$salaryTypes = SalaryType::query()->get();
		
		$resourceCollection = new EntityCollection(class_basename($this), $salaryTypes);
		
		$message = ($salaryTypes->count() <= 0) ? t('no_salary_types_found') : null;
		
		return apiResponse()->withCollection($resourceCollection, $message);
	}
	
	/**
	 * Get salary type
	 *
	 * @urlParam id int required The salary type's ID. Example: 1
	 *
	 * @param $id
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function show($id): \Illuminate\Http\JsonResponse
	{
		$salaryType = SalaryType::query()->where('id', $id);
		
		$salaryType = $salaryType->first();
		
		abort_if(empty($salaryType), 404, t('salary_type_not_found'));
		
		$resource = new SalaryTypeResource($salaryType);
		
		return apiResponse()->withResource($resource);
	}
}
