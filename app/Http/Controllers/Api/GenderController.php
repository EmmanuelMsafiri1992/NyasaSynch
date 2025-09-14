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

use App\Enums\Gender;

/**
 * @group Users
 */
class GenderController extends BaseController
{
	/**
	 * List genders
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function index(): \Illuminate\Http\JsonResponse
	{
		$genders = Gender::all();
		
		$message = empty($genders) ? t('no_genders_found') : null;
		
		$data = [
			'success' => true,
			'message' => $message,
			'result'  => $genders,
		];
		
		return apiResponse()->json($data);
	}
	
	/**
	 * Get gender
	 *
	 * @urlParam id int required The gender's ID. Example: 1
	 *
	 * @param $id
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function show($id): \Illuminate\Http\JsonResponse
	{
		$gender = Gender::find($id);
		
		abort_if(empty($gender), 404, t('gender_not_found'));
		
		$data = [
			'success' => true,
			'result'  => $gender,
		];
		
		return apiResponse()->json($data);
	}
}
