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

use App\Rules\PurchaseCodeRule;

class PluginRequest extends Request
{
	protected array|\stdClass|null $plugin = null;
	
	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array
	 */
	public function rules(): array
	{
		$rules = [];
		
		$this->plugin = load_plugin($this->segment(3));
		if (!empty($this->plugin)) {
			$pluginId = data_get($this->plugin, 'item_id');
			if (!empty($pluginId)) {
				$rules['purchase_code'] = ['required', new PurchaseCodeRule($pluginId)];
			}
		}
		
		return $rules;
	}
	
	/**
	 * Handle a passed validation attempt.
	 *
	 * @return void
	 */
	protected function passedValidation(): void
	{
		if (empty($this->plugin)) return;
		
		$pluginName = data_get($this->plugin, 'name');
		$purchaseCode = $this->input('purchase_code');
		
		if (empty($pluginName)) return;
		
		$pluginFile = storage_path('framework/plugins/' . $pluginName);
		file_put_contents($pluginFile, $purchaseCode);
	}
}
