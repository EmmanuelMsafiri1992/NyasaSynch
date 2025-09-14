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

namespace App\Http\Requests\Admin;

class SettingRequest extends Request
{
	protected array $rulesMessages = [];
	protected array $rulesAttributes = [];
	
	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array
	 */
	public function rules(): array
	{
		$rules = [];
		
		// Get right model class & its segment index
		$segmentIndex = 3;
		$model = '\App\Models\Setting';
		if (str_contains(currentRouteAction(), 'DomainSettingController')) {
			$segmentIndex = 5;
			$model = '\extras\plugins\domainmapping\app\Models\DomainSetting';
		}
		if (!class_exists($model)) return $rules;
		
		// Get the setting's ID
		$settingId = $this->segment($segmentIndex);
		if (empty($settingId)) return $rules;
		
		/**
		 * Get the setting
		 *
		 * @var \Illuminate\Database\Eloquent\Model $model
		 */
		$setting = $model::find($settingId);
		if (empty($setting)) return $rules;
		
		// Get valid setting key
		$classKey = $setting->key ?? '';
		
		// Get class name
		$className = str($classKey)->camel()->ucfirst()->append('Request');
		
		// Get class full qualified name (i.e. with namespace)
		$namespace = '\App\Http\Requests\Admin\SettingRequest\\';
		$class = $className->prepend($namespace)->toString();
		
		// If the class doesn't exist in the core app, try to get it from add-ons
		if (!class_exists($class)) {
			$namespace = plugin_namespace($classKey) . '\app\Http\Requests\Admin\SettingRequest\\';
			$class = $className->prepend($namespace)->toString();
		}
		
		if (!class_exists($class)) return $rules;
		
		// Get the setting's rules
		$formRequest = new $class();
		$rules = $formRequest->rules();
		$this->rulesMessages = $formRequest->messages();
		$this->rulesAttributes = $formRequest->attributes();
		
		return $rules;
	}
	
	/**
	 * @return array
	 */
	public function messages(): array
	{
		$messages = [];
		
		if (!empty($this->rulesMessages)) {
			$messages = $messages + $this->rulesMessages;
		}
		
		return array_merge(parent::messages(), $messages);
	}
	
	/**
	 * @return array
	 */
	public function attributes(): array
	{
		$attributes = [];
		
		if (!empty($this->rulesAttributes)) {
			$attributes = $attributes + $this->rulesAttributes;
		}
		
		return array_merge(parent::attributes(), $attributes);
	}
}
