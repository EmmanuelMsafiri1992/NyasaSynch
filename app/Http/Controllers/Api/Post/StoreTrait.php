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

namespace App\Http\Controllers\Api\Post;

use App\Helpers\Arr;
use App\Helpers\Files\Upload;
use App\Http\Controllers\Api\Post\Store\AutoRegistrationTrait;
use App\Http\Requests\Front\PostRequest;
use App\Http\Resources\PostResource;
use App\Models\City;
use App\Models\Company;
use App\Models\Post;

trait StoreTrait
{
	use AutoRegistrationTrait;
	
	/**
	 * @param \App\Http\Requests\Front\PostRequest $request
	 * @return \Illuminate\Http\JsonResponse|mixed
	 */
	public function storePost(PostRequest $request)
	{
		// Get the Post's City
		$city = City::find($request->input('city_id', 0));
		if (empty($city)) {
			return apiResponse()->error(t('city_not_found'));
		}
		
		$authUser = auth('sanctum')->user();
		
		// Get or Create a Company
		$company = null;
		$companyId = $request->input('company_id');
		if (!empty($companyId)) {
			// Get the User's Company
			if (!empty($authUser)) {
				$company = Company::query()->where('user_id', $authUser->id)->where('id', $companyId)->first();
			}
		} else {
			// Get Company Input
			$companyInput = $request->input('company');
			if (empty($companyInput['country_code'])) {
				$companyInput['country_code'] = config('country.code');
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
				
				// Get the logo file (Normal way)
				$logoFile = $request->file('company.logo');
				if (empty($logoFile)) {
					$logoFile = $request->files->get('company.logo');
				}
				
				// Save the Company's Logo
				if (!empty($logoFile)) {
					$param = [
						'destPath' => 'files/' . strtolower($company->country_code) . '/' . $company->id,
						'width'    => (int)config('larapen.media.resize.namedOptions.company-logo.width', 800),
						'height'   => (int)config('larapen.media.resize.namedOptions.company-logo.height', 800),
						'ratio'    => config('larapen.media.resize.namedOptions.company-logo.ratio', '1'),
						'upsize'   => config('larapen.media.resize.namedOptions.company-logo.upsize', '1'),
					];
					$company->logo = Upload::image($param['destPath'], $logoFile, $param);
					
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
		if (!empty($authUser)) {
			$emailVerificationRequired = config('settings.mail.email_verification') == '1'
				&& $request->filled('email')
				&& $request->input('email') != $authUser->email;
			$phoneVerificationRequired = config('settings.sms.phone_verification') == '1'
				&& $request->filled('phone')
				&& $request->input('phone') != $authUser->phone;
		} else {
			$emailVerificationRequired = config('settings.mail.email_verification') == '1' && $request->filled('email');
			$phoneVerificationRequired = config('settings.sms.phone_verification') == '1' && $request->filled('phone');
		}
		
		// New Post
		$post = new Post();
		$input = $request->only($post->getFillable());
		foreach ($input as $key => $value) {
			$post->{$key} = $value;
		}
		
		if (!empty($authUser)) {
			// Try to use the user's possible subscription
			$authUser->loadMissing('payment');
			if (!empty($authUser->payment)) {
				$post->payment_id = $authUser->payment->id ?? null;
			}
		}
		
		// Checkboxes
		$post->negotiable = $request->input('negotiable');
		$post->phone_hidden = $request->input('phone_hidden');
		
		// Other fields
		$post->country_code = $request->input('country_code', config('country.code'));
		$post->user_id = !empty($authUser) ? $authUser->id : null;
		$post->company_id = (isset($company->id)) ? $company->id : 0;
		$post->company_name = (isset($company->name)) ? $company->name : null;
		$post->company_description = (isset($company->description)) ? $company->description : null;
		$post->lat = $city->latitude;
		$post->lon = $city->longitude;
		$post->tmp_token = md5(microtime() . mt_rand(100000, 999999));
		$post->reviewed_at = null;
		
		if ($request->anyFilled(['email', 'phone'])) {
			$post->email_verified_at = now();
			$post->phone_verified_at = now();
			
			// Email verification key generation
			if ($emailVerificationRequired) {
				$post->email_token = md5(microtime() . mt_rand());
				$post->email_verified_at = null;
			}
			
			// Mobile activation key generation
			if ($phoneVerificationRequired) {
				$post->phone_token = mt_rand(100000, 999999);
				$post->phone_verified_at = null;
			}
		}
		
		if (
			config('settings.listing_form.listings_review_activation') != '1'
			&& !$emailVerificationRequired
			&& !$phoneVerificationRequired
		) {
			$post->reviewed_at = now();
		}
		
		// Save
		$post->save();
		
		// Save the logo
		if (!empty($authUser)) {
			// For logged-in user
			$post->logo = (isset($company->logo)) ? $company->logo : null;
			
			$post->save();
		} else {
			// For guest user
			if ($request->hasFile('company.logo')) {
				$destPath = 'files/' . strtolower($post->country_code) . '/' . $post->id;
				$post->logo = Upload::image($destPath, $request->file('company.logo'));
				
				$post->save();
			}
		}
		
		$data = [
			'success' => true,
			'message' => $this->apiMsg['payable']['success'],
			'result'  => (new PostResource($post))->toArray($request),
		];
		
		$extra = [];
		
		// Auto-Register the Author
		$extra['autoRegisteredUser'] = $this->autoRegister($post, $request);
		
		$requestIsNotFromWebApp = (!doesRequestIsFromWebApp());
		if ($requestIsNotFromWebApp) {
			// ===| Make|send payment (if needed) |==============
			
			$payResult = $this->isPaymentRequested($request, $post);
			if (data_get($payResult, 'success')) {
				return $this->sendPayment($request, $post);
			}
			if (data_get($payResult, 'failure')) {
				return apiResponse()->error(data_get($payResult, 'message'));
			}
			
			// ===| If no payment is made (continue) |===========
		}
		
		$data['success'] = true;
		$data['message'] = $this->apiMsg['payable']['success'];
		
		// Send Verification Link or Code
		// Email
		if ($emailVerificationRequired) {
			// Send Verification Link by Email
			$extra['sendEmailVerification'] = $this->sendEmailVerification($post);
			if (
				array_key_exists('success', $extra['sendEmailVerification'])
				&& array_key_exists('message', $extra['sendEmailVerification'])
			) {
				$extra['mail']['success'] = $extra['sendEmailVerification']['success'];
				$extra['mail']['message'] = $extra['sendEmailVerification']['message'];
			}
		}
		
		// Phone
		if ($phoneVerificationRequired) {
			// Send Verification Code by SMS
			$extra['sendPhoneVerification'] = $this->sendPhoneVerification($post);
			if (
				array_key_exists('success', $extra['sendPhoneVerification'])
				&& array_key_exists('message', $extra['sendPhoneVerification'])
			) {
				$extra['mail']['success'] = $extra['sendPhoneVerification']['success'];
				$extra['mail']['message'] = $extra['sendPhoneVerification']['message'];
			}
		}
		
		// Once Verification Notification is sent (containing Link or Code),
		// Send Confirmation Notification, when user clicks on the Verification Link or enters the Verification Code.
		// Done in the "app/Observers/PostObserver.php" file.
		
		$data['extra'] = $extra;
		
		return apiResponse()->json($data);
	}
}
