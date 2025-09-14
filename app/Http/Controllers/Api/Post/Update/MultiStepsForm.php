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

namespace App\Http\Controllers\Api\Post\Update;

use App\Helpers\Arr;
use App\Helpers\Files\Upload;
use App\Http\Requests\Front\PostRequest;
use App\Http\Resources\PostResource;
use App\Models\City;
use App\Models\Company;
use App\Models\Post;
use App\Models\Scopes\ReviewedScope;
use App\Models\Scopes\VerifiedScope;

trait MultiStepsForm
{
	/**
	 * @param $tokenOrId
	 * @param \App\Http\Requests\Front\PostRequest $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function multiStepsFormUpdate($tokenOrId, PostRequest $request): \Illuminate\Http\JsonResponse
	{
		$authUser = auth('sanctum')->user();
		
		$countPackages = $request->input('count_packages', 0);
		$countPaymentMethods = $request->input('count_payment_methods', 0);
		$countryCode = $request->input('country_code', config('country.code'));
		
		$post = null;
		if (!empty($authUser)) {
			$post = Post::query()
				->withoutGlobalScopes([VerifiedScope::class, ReviewedScope::class])
				->inCountry($countryCode)
				->where('user_id', $authUser->getAuthIdentifier())
				->where('id', $tokenOrId)
				->first();
		}
		
		if (empty($post)) {
			return apiResponse()->notFound(t('post_not_found'));
		}
		
		// Get the Post's City
		$city = City::find($request->input('city_id', 0));
		if (empty($city)) {
			return apiResponse()->error(t('city_not_found'));
		}
		
		// Get or Create a Company
		$company = null;
		$companyId = $request->input('company_id');
		if (!empty($companyId)) {
			// Get the User's Company
			if (!empty($authUser)) {
				$company = Company::where('user_id', $authUser->id)->where('id', $companyId)->first();
			}
		} else {
			// Get Company Input
			$companyInput = $request->input('company');
			if (empty($companyInput['country_code'])) {
				$companyInput['country_code'] = $countryCode;
			}
			
			// Logged Users
			if (!empty($authUser)) {
				if (empty($companyInput['user_id'])) {
					$companyInput['user_id'] = $authUser->id;
				}
				
				// Store the User's Company
				$company = new Company();
				foreach ($companyInput as $key => $value) {
					if (in_array($key, $company->getFillable())) {
						$company->{$key} = $value;
					}
				}
				$company->save();
				
				// Save the Company's Logo
				if ($request->hasFile('company.logo')) {
					$param = [
						'destPath' => 'files/' . strtolower($company->country_code) . '/' . $company->id,
						'width'    => (int)config('larapen.media.resize.namedOptions.company-logo.width', 800),
						'height'   => (int)config('larapen.media.resize.namedOptions.company-logo.height', 800),
						'ratio'    => config('larapen.media.resize.namedOptions.company-logo.ratio', '1'),
						'upsize'   => config('larapen.media.resize.namedOptions.company-logo.upsize', '1'),
					];
					$company->logo = Upload::image($param['destPath'], $request->file('company.logo'), $param);
					
					$company->save();
				}
			} else {
				// Guest Users
				$company = Arr::toObject($companyInput);
			}
		}
		
		// Return error if a company is not set
		if (empty($company)) {
			$message = t('Please select a company or New Company to create one');
			
			return apiResponse()->error($message);
		}
		
		// Conditions to Verify User's Email or Phone
		$emailVerificationRequired = config('settings.mail.email_verification') == '1'
			&& $request->filled('email')
			&& $request->input('email') != $post->email;
		$phoneVerificationRequired = config('settings.sms.phone_verification') == '1'
			&& $request->filled('phone')
			&& $request->input('phone') != $post->phone;
		
		/*
		 * Allow admin users to approve the changes,
		 * If the listing approbation option is enabled, and if important data have been changed.
		 */
		if (config('settings.listing_form.listings_review_activation')) {
			if (
				md5($post->title) != md5($request->input('title'))
				|| md5($post->company_description) != md5((isset($company->description)) ? $company->description : null)
				|| md5($post->description) != md5($request->input('description'))
				|| md5($post->application_url) != md5($request->input('application_url'))
			) {
				$post->reviewed_at = null;
			}
		}
		
		// Update Post
		$input = $request->only($post->getFillable());
		foreach ($input as $key => $value) {
			$post->{$key} = $value;
		}
		
		// Checkboxes
		$post->negotiable = $request->input('negotiable');
		$post->phone_hidden = $request->input('phone_hidden');
		
		// Other fields
		$post->company_id = (isset($company->id)) ? $company->id : 0;
		$post->company_name = (isset($company->name)) ? $company->name : null;
		$post->logo = (isset($company->logo)) ? $company->logo : null;
		$post->company_description = (isset($company->description)) ? $company->description : null;
		$post->lat = $city->latitude;
		$post->lon = $city->longitude;
		
		// Email verification key generation
		if ($emailVerificationRequired) {
			$post->email_token = md5(microtime() . mt_rand());
			$post->email_verified_at = null;
		}
		
		// Phone verification key generation
		if ($phoneVerificationRequired) {
			$post->phone_token = mt_rand(100000, 999999);
			$post->phone_verified_at = null;
		}
		
		// Save
		$post->save();
		
		// Save the logo (For guest user)
		if (empty($authUser)) {
			if ($request->hasFile('company.logo')) {
				$destPath = 'files/' . strtolower($post->country_code) . '/' . $post->id;
				$post->logo = Upload::image($destPath, $request->file('company.logo'));
				
				$post->save();
			}
		}
		
		$data = [
			'success' => true,
			'message' => t('your_listing_is_updated'),
			'result'  => (new PostResource($post))->toArray($request),
		];
		
		$extra = [];
		
		// User should he go on Payment page or not?
		$shouldHeGoOnPaymentPage = (
			is_numeric($countPackages)
			&& is_numeric($countPaymentMethods)
			&& $countPackages > 0
			&& $countPaymentMethods > 0
		);
		if ($shouldHeGoOnPaymentPage) {
			$extra['steps']['payment'] = true;
		} else {
			$extra['steps']['payment'] = false;
		}
		
		// Send an Email Verification message
		if ($emailVerificationRequired) {
			$extra['sendEmailVerification'] = $this->sendEmailVerification($post);
			if (
				array_key_exists('success', $extra['sendEmailVerification'])
				&& array_key_exists('message', $extra['sendEmailVerification'])
			) {
				$extra['mail']['success'] = $extra['sendEmailVerification']['success'];
				$extra['mail']['message'] = $extra['sendEmailVerification']['message'];
			}
		}
		
		// Send a Phone Verification message
		if ($phoneVerificationRequired) {
			$extra['sendPhoneVerification'] = $this->sendPhoneVerification($post);
			if (
				array_key_exists('success', $extra['sendPhoneVerification'])
				&& array_key_exists('message', $extra['sendPhoneVerification'])
			) {
				$extra['mail']['success'] = $extra['sendPhoneVerification']['success'];
				$extra['mail']['message'] = $extra['sendPhoneVerification']['message'];
			}
		}
		
		$data['extra'] = $extra;
		
		return apiResponse()->json($data);
	}
}
