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

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResumeResource extends JsonResource
{
	/**
	 * Transform the resource into an array.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return array
	 */
	public function toArray(Request $request): array
	{
		$entity = [
			'id'           => $this->id,
			'country_code' => $this->country_code,
			'name'         => $this->name,
		];
		
		$embed = explode(',', request()->input('embed'));
		
		$authUser = auth('sanctum')->user();
		if (!empty($authUser)) {
			$userId = (in_array('user', $embed)) ? $this->user->id : $this->user_id;
			$isAuthUserData = ($userId == $authUser->getAuthIdentifier());
			
			if ($isAuthUserData) {
				$columns = $this->getFillable();
				foreach ($columns as $column) {
					$entity[$column] = $this->{$column};
				}
			}
		}
		
		$entity['country_flag_url'] = $this->country_flag_url ?? null;
		
		if (in_array('user', $embed)) {
			$entity['user'] = new UserResource($this->whenLoaded('user'));
		}
		
		return $entity;
	}
}
