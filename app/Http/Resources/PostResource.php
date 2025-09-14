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

class PostResource extends JsonResource
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
			'id' => $this->id ?? null,
		];
		
		$columns = $this->getFillable();
		foreach ($columns as $column) {
			$entity[$column] = $this->{$column} ?? null;
			if ($column == 'title') {
				$entity['excerpt'] = $this->excerpt ?? null;
			}
		}
		
		$entity['reference'] = $this->id ? ($this->reference ?? null) : null;
		$entity['slug'] = $this->slug ?? null;
		$entity['url'] = $this->id ? ($this->url ?? null) : null;
		$entity['phone_intl'] = $this->phone_intl ?? null;
		$entity['created_at_formatted'] = $this->created_at_formatted ?? null;
		$entity['user_photo_url'] = $this->user_photo_url ?? null;
		$entity['country_flag_url'] = $this->country_flag_url ?? null;
		$entity['salary_formatted'] = $this->salary_formatted ?? null;
		$entity['visits_formatted'] = $this->visits_formatted ?? null;
		$entity['distance_info'] = $this->distance_info ?? null;
		
		$defaultLogo = config('larapen.media.picture');
		$defaultLogoUrl = imgUrl($defaultLogo);
		$entity['logo_url'] = [
			'full'   => $this->logo_url ?? $defaultLogoUrl,
			'small'  => $this->logo_url_small ?? $defaultLogoUrl,
			'medium' => $this->logo_url_medium ?? $defaultLogoUrl,
			'large'  => $this->logo_url_large ?? $defaultLogoUrl,
		];
		
		if (isset($this->distance)) {
			$entity['distance'] = $this->distance;
		}
		
		$embed = request()->filled('embed') ? explode(',', request()->input('embed')) : [];
		
		if (in_array('country', $embed)) {
			$entity['country'] = new CountryResource($this->whenLoaded('country'));
		}
		if (in_array('user', $embed)) {
			$entity['user'] = new UserResource($this->whenLoaded('user'));
		}
		if (in_array('category', $embed)) {
			$entity['category'] = new CategoryResource($this->whenLoaded('category'));
		}
		if (in_array('postType', $embed)) {
			$entity['postType'] = new PostTypeResource($this->whenLoaded('postType'));
		}
		if (in_array('city', $embed)) {
			$entity['city'] = new CityResource($this->whenLoaded('city'));
		}
		if (in_array('currency', $embed)) {
			$entity['currency'] = new CurrencyResource($this->whenLoaded('currency'));
		}
		if (in_array('payment', $embed)) {
			$entity['payment'] = new PaymentResource($this->whenLoaded('payment'));
		}
		if (in_array('possiblePayment', $embed)) {
			$entity['possiblePayment'] = new PaymentResource($this->whenLoaded('possiblePayment'));
		}
		if (in_array('savedByLoggedUser', $embed)) {
			$entity['savedByLoggedUser'] = UserResource::collection($this->whenLoaded('savedByLoggedUser'));
		}
		if (in_array('company', $embed)) {
			$entity['company'] = new CompanyResource($this->whenLoaded('company'));
		}
		
		return $entity;
	}
}
