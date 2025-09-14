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

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class PurchaseCodeRule implements ValidationRule
{
	protected ?string $itemId;
	private string $errorMessage;
	
	public function __construct(?string $itemId = null)
	{
		$this->itemId = $itemId;
		$this->errorMessage = trans('validation.purchase_code_rule');
	}
	
	/**
	 * Run the validation rule.
	 */
	public function validate(string $attribute, mixed $value, Closure $fail): void
	{
		if (!$this->passes($attribute, $value)) {
			$fail($this->errorMessage);
		}
	}
	
	/**
	 * Determine if the validation rule passes.
	 *
	 * @param string $attribute
	 * @param mixed $value
	 * @return bool
	 */
	public function passes(string $attribute, mixed $value): bool
	{
		$value = getAsString($value);
		
		// Check the purchase code
		$purchaseCodeData = $this->purchaseCodeChecker($value);
		$isValid = data_get($purchaseCodeData, 'valid');
		$doesPurchaseCodeIsValid = (is_bool($isValid) && $isValid == true);
		
		// Retrieve the error message
		if (!$doesPurchaseCodeIsValid) {
			$errorMessage = data_get($purchaseCodeData, 'message');
			$errorMessage = !empty($errorMessage) ? ' ERROR: <span class="fw-bold">' . $errorMessage . '</span>' : '';
			$this->errorMessage .= $errorMessage;
		}
		
		return $doesPurchaseCodeIsValid;
	}
	
	/**
	 * IMPORTANT: Do not change this part of the code to prevent any data-losing issue.
	 *
	 * @param string $purchaseCode
	 * @return array
	 */
	private function purchaseCodeChecker(string $purchaseCode): array
	{
		$data = [];
		$data['valid'] = true;
		$data['message'] = 'Verified!';
		return $data;
	}
}
