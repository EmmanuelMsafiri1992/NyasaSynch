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

namespace App\Http\Requests\Front;

use App\Helpers\Num;
use App\Helpers\RemoveFromString;
use App\Http\Requests\Front\PostRequest\LimitationCompliance;
use App\Http\Requests\Request;
use App\Models\Package;
use App\Models\Post;
use App\Rules\BetweenRule;
use App\Rules\BlacklistTitleRule;
use App\Rules\BlacklistWordRule;
use App\Rules\DateIsValidRule;
use App\Rules\MbAlphanumericRule;
use App\Rules\SluggableRule;
use App\Rules\UniquenessOfPostRule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Number;
use Illuminate\Validation\Rule;
use Mews\Purifier\Facades\Purifier;

class PostRequest extends Request
{
	public static Collection $packages;
	public static Collection $paymentMethods;
	
	protected array $limitationComplianceMessages = [];
	
	/**
	 * Prepare the data for validation.
	 *
	 * @return void
	 */
	protected function prepareForValidation()
	{
		// Don't apply this to the Admin Panel
		if (isAdminPanel()) {
			return;
		}
		
		$input = $this->all();
		
		// title
		if ($this->filled('title')) {
			$input['title'] = $this->input('title');
			$input['title'] = strCleaner($input['title']);
			$input['title'] = preventStringContainingOnlyNumericChars($input['title']);
			$input['title'] = RemoveFromString::contactInfo($input['title'], true);
		}
		
		// company.name
		if ($this->filled('company.name')) {
			$input['company']['name'] = $this->input('company.name');
			$input['company']['name'] = preventStringContainingOnlyNumericChars($input['company']['name']);
			$input['company']['name'] = RemoveFromString::contactInfo($input['company']['name'], true);
		}
		
		// company.description
		if ($this->filled('company.description')) {
			$input['company']['description'] = $this->input('company.description');
			$input['company']['description'] = preventStringContainingOnlyNumericChars($input['company']['description']);
			$input['company']['description'] = RemoveFromString::contactInfo($input['company']['description'], true);
		}
		
		// description
		if ($this->filled('description')) {
			$input['description'] = $this->input('description');
			$input['description'] = preventStringContainingOnlyNumericChars($input['description']);
			if (config('settings.listing_form.wysiwyg_editor') != 'none') {
				try {
					$input['description'] = Purifier::clean($input['description']);
				} catch (\Exception $e) {
				}
			} else {
				$input['description'] = mbStrCleaner($input['description']);
			}
			$input['description'] = RemoveFromString::contactInfo($input['description'], true);
		}
		
		// salary_min
		if ($this->has('salary_min')) {
			if ($this->filled('salary_min')) {
				$input['salary_min'] = $this->input('salary_min');
				// If field's value contains only numbers and dot,
				// Then decimal separator is set as dot.
				if (preg_match('/^[\d.]*$/', $input['salary_min'])) {
					$input['salary_min'] = Num::formatForDb($input['salary_min'], '.');
				} else {
					if ($this->filled('currency_decimal_separator')) {
						$input['salary_min'] = Num::formatForDb($input['salary_min'], $this->input('currency_decimal_separator'));
					} else {
						$input['salary_min'] = Num::formatForDb($input['salary_min'], config('currency.decimal_separator', '.'));
					}
				}
			} else {
				$input['salary_min'] = null;
			}
		}
		
		// salary_max
		if ($this->has('salary_max')) {
			if ($this->filled('salary_max')) {
				$input['salary_max'] = $this->input('salary_max');
				// If field's value contains only numbers and dot,
				// Then decimal separator is set as dot.
				if (preg_match('/^[\d.]*$/', $input['salary_max'])) {
					$input['salary_max'] = Num::formatForDb($input['salary_max'], '.');
				} else {
					if ($this->filled('currency_decimal_separator')) {
						$input['salary_max'] = Num::formatForDb($input['salary_max'], $this->input('currency_decimal_separator'));
					} else {
						$input['salary_max'] = Num::formatForDb($input['salary_max'], config('currency.decimal_separator', '.'));
					}
				}
			} else {
				$input['salary_max'] = null;
			}
		}
		
		// currency_code
		if ($this->filled('currency_code')) {
			$input['currency_code'] = $this->input('currency_code');
		} else {
			$input['currency_code'] = config('currency.code', 'USD');
		}
		
		// contact_name
		if ($this->filled('contact_name')) {
			$input['contact_name'] = strCleaner($this->input('contact_name'));
			$input['contact_name'] = preventStringContainingOnlyNumericChars($input['contact_name']);
		}
		
		// auth_field
		$input['auth_field'] = getAuthField();
		
		// phone
		if ($this->filled('phone')) {
			$input['phone'] = phoneE164($this->input('phone'), getPhoneCountry());
			$input['phone_national'] = phoneNational($this->input('phone'), getPhoneCountry());
		} else {
			$input['phone'] = null;
			$input['phone_national'] = null;
		}
		
		// tags
		if ($this->filled('tags')) {
			$input['tags'] = tagCleaner($this->input('tags'));
		}
		
		// application_url
		if ($this->filled('application_url')) {
			$input['application_url'] = addHttp($this->input('application_url'));
		}
		
		request()->merge($input); // Required!
		$this->merge($input);
	}
	
	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array
	 */
	public function rules()
	{
		$createMethods = ['POST', 'CREATE'];
		$updateMethods = ['PUT', 'PATCH', 'UPDATE'];
		
		$guard = isFromApi() ? 'sanctum' : null;
		$authFields = array_keys(getAuthFields());
		
		$rules = [];
		
		$rules['category_id'] = ['required', 'not_in:0', 'exists:categories,id'];
		$rules['post_type_id'] = ['required', 'not_in:0', 'exists:post_types,id'];
		$rules['title'] = [
			'required',
			new BetweenRule(
				(int)config('settings.listing_form.title_min_length', 2),
				(int)config('settings.listing_form.title_max_length', 150)
			),
			new MbAlphanumericRule(),
			new SluggableRule(),
			new BlacklistTitleRule(),
		];
		if (config('settings.listing_form.enable_post_uniqueness')) {
			$rules['title'][] = new UniquenessOfPostRule();
		}
		$rules['description'] = [
			'required',
			new BetweenRule(
				(int)config('settings.listing_form.description_min_length', 5),
				(int)config('settings.listing_form.description_max_length', 12000)
			),
			new MbAlphanumericRule(),
			new BlacklistWordRule(),
		];
		$rules['salary_type_id'] = ['required', 'not_in:0', 'exists:salary_types,id'];
		$rules['salary_min'] = ['required_with:salary_max'];
		$rules['salary_max'] = ['required_with:salary_min', 'gte:salary_min'];
		$rules['contact_name'] = ['required', new BetweenRule(2, 200)];
		$rules['auth_field'] = ['required', Rule::in($authFields)];
		$rules['phone'] = ['max:30'];
		$rules['phone_country'] = ['required_with:phone'];
		$rules['city_id'] = ['required', 'not_in:0', 'exists:cities,id'];
		
		if (!auth($guard)->check()) {
			$rules['accept_terms'] = ['accepted'];
		}
		
		$isSingleStepForm = (config('settings.listing_form.publication_form_type') == '2');
		
		// CREATE
		if (in_array($this->method(), $createMethods)) {
			$rules['start_date'] = [new DateIsValidRule('future')];
			
			if ($isSingleStepForm) {
				// Require 'package_id' if Packages are available
				$isPackageSelectionRequired = (
					isset(self::$packages, self::$paymentMethods)
					&& self::$packages->count() > 0
					&& self::$paymentMethods->count() > 0
				);
				if ($isPackageSelectionRequired) {
					$rules['package_id'] = 'required';
					
					if ($this->has('package_id')) {
						$package = Package::find($this->input('package_id'));
						
						// Require 'payment_method_id' if the selected package's price > 0
						$isPaymentMethodSelectionRequired = (!empty($package) && $package->price > 0);
						if ($isPaymentMethodSelectionRequired) {
							$rules['payment_method_id'] = 'required|not_in:0';
						}
					}
				}
			}
			
			$rules = $this->captchaRules($rules);
		}
		
		// UPDATE
		if (in_array($this->method(), $updateMethods)) {
			if ($this->filled('post_id')) {
				$post = Post::find($this->input('post_id'));
				$rules['start_date'] = [new DateIsValidRule('future', ($post->created_at ?? null))];
			} else {
				$rules['start_date'] = [new DateIsValidRule('future')];
			}
		}
		
		// COMMON
		
		// Location
		if (config('settings.listing_form.city_selection') == 'select') {
			$adminType = config('country.admin_type', 0);
			if (in_array($adminType, ['1', '2'])) {
				$cityId = $this->integer('city_id');
				$isCityIdFilled = (!empty($cityId) && $cityId > 0);
				if (!$isCityIdFilled) {
					$rules['admin_code'] = ['required', 'not_in:0'];
				}
			}
		}
		
		$phoneIsEnabledAsAuthField = (config('settings.sms.enable_phone_as_auth_field') == '1');
		$phoneNumberIsRequired = ($phoneIsEnabledAsAuthField && $this->input('auth_field') == 'phone');
		
		// email
		$emailIsRequired = (!$phoneNumberIsRequired);
		if ($emailIsRequired) {
			$rules['email'][] = 'required';
		}
		$rules = $this->validEmailRules('email', $rules);
		
		// phone
		if ($phoneNumberIsRequired) {
			$rules['phone'][] = 'required';
		}
		$rules = $this->validPhoneNumberRules('phone', $rules);
		
		// Company
		$companyId = $this->input('company_id');
		if (empty($companyId)) {
			$rules['company.name'] = ['required', new BetweenRule(2, 200), new BlacklistTitleRule()];
			$rules['company.description'] = ['required', new BetweenRule(5, 12000), new BlacklistWordRule()];
			
			// Check 'company.logo' is required
			if ($this->file('company.logo')) {
				$rules['company.logo'] = [
					'required',
					'image',
					'mimes:' . getUploadFileTypes('image'),
					'max:' . (int)config('settings.upload.max_image_size', 1000),
				];
			}
		} else {
			$rules['company_id'] = ['required', 'not_in:0', 'exists:companies,id'];
		}
		
		// Application URL
		if ($this->filled('application_url')) {
			$rules['application_url'] = ['url'];
		}
		
		// Tags
		if ($this->filled('tags')) {
			$rules['tags.*'] = ['regex:' . tagRegexPattern(), new BlacklistWordRule()];
		}
		
		// Posts Limitation Compliance
		if (in_array($this->method(), $createMethods)) {
			$limitationComplianceRequest = new LimitationCompliance();
			$rules = $rules + $limitationComplianceRequest->rules();
			$this->limitationComplianceMessages = $limitationComplianceRequest->messages();
		}
		
		return $rules;
	}
	
	/**
	 * Get custom attributes for validator errors.
	 *
	 * @return array
	 */
	public function attributes(): array
	{
		$attributes = [];
		
		if ($this->file('company.logo')) {
			$attributes['company.logo'] = t('logo');
		}
		
		if ($this->filled('tags')) {
			$tags = $this->input('tags');
			if (is_array($tags) && !empty($tags)) {
				foreach ($tags as $key => $tag) {
					$attributes['tags.' . $key] = t('tag X', ['key' => ($key + 1)]);
				}
			}
		}
		
		return $attributes;
	}
	
	/**
	 * @return array
	 */
	public function messages(): array
	{
		$messages = [];
		
		// Logo
		if ($this->file('company.logo')) {
			// uploaded
			$maxSize = (int)config('settings.upload.max_image_size', 1000); // In KB
			$maxSize = $maxSize * 1024;                                     // Convert KB to Bytes
			$msg = t('large_file_uploaded_error', [
				'field'   => t('logo'),
				'maxSize' => Number::fileSize($maxSize),
			]);
			
			$uploadMaxFilesizeStr = @ini_get('upload_max_filesize');
			$postMaxSizeStr = @ini_get('post_max_size');
			if (!empty($uploadMaxFilesizeStr) && !empty($postMaxSizeStr)) {
				$uploadMaxFilesize = forceToInt($uploadMaxFilesizeStr);
				$postMaxSize = forceToInt($postMaxSizeStr);
				
				$serverMaxSize = min($uploadMaxFilesize, $postMaxSize);
				$serverMaxSize = $serverMaxSize * 1024 * 1024; // Convert MB to KB to Bytes
				if ($serverMaxSize < $maxSize) {
					$msg = t('large_file_uploaded_error_system', [
						'field'   => t('logo'),
						'maxSize' => Number::fileSize($serverMaxSize),
					]);
				}
			}
			
			$messages['company.logo.uploaded'] = $msg;
		}
		
		// Category & Sub-Category
		if ($this->filled('parent_id') && !empty($this->input('parent_id'))) {
			$messages['category_id.required'] = t('The field is required', ['field' => mb_strtolower(t('Sub-Category'))]);
			$messages['category_id.not_in'] = t('The field is required', ['field' => mb_strtolower(t('Sub-Category'))]);
		}
		
		$isSingleStepForm = (config('settings.listing_form.publication_form_type') == '2');
		if ($isSingleStepForm) {
			// Package & PaymentMethod
			$messages['package_id.required'] = trans('validation.required_package_id');
			$messages['payment_method_id.required'] = t('validation.required_payment_method_id');
			$messages['payment_method_id.not_in'] = t('validation.required_payment_method_id');
		}
		
		// Posts Limitation Compliance
		$messages = $messages + $this->limitationComplianceMessages;
		
		return $messages;
	}
}
