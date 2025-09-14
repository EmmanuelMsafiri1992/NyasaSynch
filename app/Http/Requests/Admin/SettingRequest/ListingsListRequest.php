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

class ListingsListRequest extends Request
{
	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array
	 */
	public function rules(): array
	{
		// $request = request();
		
		$rules = [
			'min_salary'         => ['required', 'integer', 'min:0', 'lte:max_price'],
			'max_salary'         => ['required', 'integer', 'max:1000000', 'gte:min_price'],
			'salary_slider_step' => ['required', 'integer', 'min:1', 'max:10000'],
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
			'min_salary'         => trans('admin.min_salary_label'),
			'max_salary'         => trans('admin.max_salary_label'),
			'salary_slider_step' => trans('admin.salary_slider_step_label'),
		];
		
		return array_merge(parent::attributes(), $attributes);
	}
}
