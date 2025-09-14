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

namespace App\Http\Requests\Admin\SettingRequest;

use App\Http\Requests\Request;

/*
 * Use request() instead of $this since this form request can be called from another
 */

class ListingFormRequest extends Request
{
	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array
	 */
	public function rules(): array
	{
		// $request = request();
		
		// Some MySQL stringable column types limit
		$varcharLimit = 255;
		$textLimit = 65535;
		
		$rules = [
			'title_min_length'       => ['required', 'integer', 'min:2', 'lte:title_max_length'],
			'title_max_length'       => ['required', 'integer', 'max:' . $varcharLimit, 'gte:title_min_length'],
			'description_min_length' => ['required', 'integer', 'min:2', 'lte:description_max_length'],
			'description_max_length' => ['required', 'integer', 'max:' . $textLimit, 'gte:description_min_length'],
			'tags_min_length'        => ['required', 'integer', 'min:2', 'lte:tags_max_length'],
			'tags_max_length'        => ['required', 'integer', 'max:' . $varcharLimit, 'gte:tags_min_length'],
		];
		
		return $rules;
	}
	
	/**
	 * @return array
	 */
	public function messages(): array
	{
		$messages = [];
		
		return array_merge(parent::messages(), $messages);
	}
	
	/**
	 * @return array
	 */
	public function attributes(): array
	{
		$attributes = [
			'title_min_length'       => trans('admin.title_min_length_label'),
			'title_max_length'       => trans('admin.title_max_length_label'),
			'description_min_length' => trans('admin.description_min_length_label'),
			'description_max_length' => trans('admin.description_max_length_label'),
			'tags_min_length'        => trans('admin.tags_min_length_label'),
			'tags_max_length'        => trans('admin.tags_max_length_label'),
		];
		
		return array_merge(parent::attributes(), $attributes);
	}
}
