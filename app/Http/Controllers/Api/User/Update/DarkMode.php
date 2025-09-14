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

namespace App\Http\Controllers\Api\User\Update;

use App\Http\Resources\UserResource;
use App\Models\Scopes\VerifiedScope;
use App\Models\User;
use Illuminate\Http\Request;

trait DarkMode
{
	/**
	 * Remove the User's photo
	 *
	 * @param $userId
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function setDarkMode($userId, Request $request): \Illuminate\Http\JsonResponse
	{
		$user = User::withoutGlobalScopes([VerifiedScope::class])->where('id', $userId)->first();
		
		if (empty($user)) {
			return apiResponse()->notFound(t('user_not_found'));
		}
		
		$authUser = auth('sanctum')->user();
		if (empty($authUser)) {
			return apiResponse()->unauthorized();
		}
		
		// Check logged User
		if ($authUser->getAuthIdentifier() != $user->id) {
			return apiResponse()->unauthorized();
		}
		
		// Set the dark mode in the DB
		$user->dark_mode = $request->input('dark_mode');
		$user->save();
		
		// Result data
		$data = [
			'success' => true,
			'message' => ($user->dark_mode == 1) ? t('dark_mode_is_set') : t('dark_mode_is_disabled'),
			'result'  => (new UserResource($user))->toArray($request),
		];
		
		return apiResponse()->json($data);
	}
}
