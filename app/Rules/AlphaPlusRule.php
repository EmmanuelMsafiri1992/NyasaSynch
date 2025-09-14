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

class AlphaPlusRule implements ValidationRule
{
	protected string|array $additionalChars;
	protected string $additionalCharsFormatted;
	
	/**
	 * Constructor to accept additional characters.
	 *
	 * @param string|array $additionalChars
	 */
	public function __construct(string|array $additionalChars = '')
	{
		$this->additionalChars = is_array($additionalChars) ? implode('', $additionalChars) : $additionalChars;
		$this->additionalCharsFormatted = $this->formatAdditionalChars($this->additionalChars);
	}
	
	/**
	 * Run the validation rule.
	 */
	public function validate(string $attribute, mixed $value, Closure $fail): void
	{
		if (!$this->passes($attribute, $value)) {
			$message = $this->additionalChars
				? trans('validation.alphabetic_plus_rule', ['additionalChars' => $this->additionalCharsFormatted])
				: trans('validation.alphabetic_only_rule');
			$fail($message);
		}
	}
	
	/**
	 * Determine if the validation rule passes.
	 *
	 * @param  string  $attribute
	 * @param  mixed  $value
	 * @return bool
	 */
	public function passes(string $attribute, mixed $value): bool
	{
		$pattern = '/^[a-zA-Z' . preg_quote($this->additionalChars, '/') . ']+$/';
		return preg_match($pattern, $value);
	}
	
	/**
	 * Format additional characters as a comma-separated string.
	 *
	 * @param  string  $additionalChars
	 * @return string
	 */
	protected function formatAdditionalChars(string $additionalChars): string
	{
		return implode(', ', str_split($additionalChars));
	}
}
