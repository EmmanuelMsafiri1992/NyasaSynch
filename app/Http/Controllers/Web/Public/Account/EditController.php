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

namespace App\Http\Controllers\Web\Public\Account;

use App\Enums\Gender;
use App\Enums\UserType;
use App\Http\Controllers\Web\Public\Auth\Traits\VerificationTrait;
use App\Http\Requests\Front\AvatarRequest;
use App\Http\Requests\Front\UserRequest;
use Larapen\LaravelMetaTags\Facades\MetaTag;

class EditController extends AccountBaseController
{
	use VerificationTrait;
	
	/**
	 * @return \Illuminate\Contracts\View\View
	 */
	public function index()
	{
		$genders = Gender::all();
		$userTypes = UserType::all();

		$authUser = auth()->user();

		// Ensure user is authenticated
		if (!$authUser) {
			abort(401, 'Authentication required');
		}
		
		// User Type missing notification
		if (empty($authUser->user_type_id)) {
			flash(t('select_a_user_type_to_start'))->warning();
		}
		
		$appName = config('settings.app.name', 'Site Name');
		$title = t('my_account') . ' - ' . $appName;
		
		// Meta Tags
		MetaTag::set('title', $title);
		MetaTag::set('description', t('my_account_on', ['appName' => config('settings.app.name')]));
		
		return appView('account.edit', compact('genders', 'userTypes', 'authUser'));
	}
	
	/**
	 * @param \App\Http\Requests\Front\UserRequest $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function updateDetails(UserRequest $request)
	{
		$authUser = auth()->user();
		
		// Call API endpoint
		$endpoint = '/users/' . $authUser->getAuthIdentifier();
		$data = makeApiRequest('put', $endpoint, $request->all());
		
		// Parsing the API response
		$message = data_get($data, 'message', t('unknown_error'));
		
		// HTTP Error Found
		if (!data_get($data, 'isSuccessful')) {
			flash($message)->error();
			
			return redirect()->back()->withInput($request->except(['photo']));
		}
		
		// Notification Message
		if (data_get($data, 'success')) {
			flash($message)->success();
		} else {
			flash($message)->error();
		}
		
		// Get User Resource
		$user = data_get($data, 'result');
		
		// Don't log out the User (See the User model's file)
		if (data_get($data, 'extra.emailOrPhoneChanged')) {
			session()->put('emailOrPhoneChanged', true);
		}
		
		// Get Query String
		$queryString = '';
		if ($request->filled('panel')) {
			$queryString = '?panel=' . $request->input('panel');
		}
		
		// Get the next URL
		$nextUrl = url('account' . $queryString);
		
		if (
			data_get($data, 'extra.sendEmailVerification.emailVerificationSent')
			|| data_get($data, 'extra.sendPhoneVerification.phoneVerificationSent')
		) {
			session()->put('userNextUrl', $nextUrl);
			
			if (data_get($data, 'extra.sendEmailVerification.emailVerificationSent')) {
				session()->put('emailVerificationSent', true);
				
				// Show the Re-send link
				$this->showReSendVerificationEmailLink($user, 'users');
			}
			
			if (data_get($data, 'extra.sendPhoneVerification.phoneVerificationSent')) {
				session()->put('phoneVerificationSent', true);
				
				// Show the Re-send link
				$this->showReSendVerificationSmsLink($user, 'users');
				
				// Go to Phone Number verification
				$nextUrl = url('users/verify/phone/');
			}
		}
		
		// Mail Notification Message
		if (data_get($data, 'extra.mail.message')) {
			$mailMessage = data_get($data, 'extra.mail.message');
			if (data_get($data, 'extra.mail.success')) {
				flash($mailMessage)->success();
			} else {
				flash($mailMessage)->error();
			}
		}
		
		return redirect()->to($nextUrl);
	}
	
	/**
	 * Update the User's photo.
	 *
	 * @param \App\Http\Requests\Front\AvatarRequest $request
	 * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
	 */
	public function updatePhoto(AvatarRequest $request)
	{
		$authUser = auth()->user();
		
		// Call API endpoint
		$endpoint = '/users/' . $authUser->getAuthIdentifier() . '/photo';
		$data = makeApiRequest('put', $endpoint, $request->all(), [], true);
		
		// Parsing the API response
		return $this->handlePhotoApiData($data);
	}
	
	/**
	 * Delete the User's photo.
	 *
	 * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
	 */
	public function deletePhoto()
	{
		$authUser = auth()->user();
		
		// Call API endpoint
		$endpoint = '/users/' . $authUser->getAuthIdentifier() . '/photo/delete';
		$data = makeApiRequest('get', $endpoint);
		
		// Parsing the API response
		return $this->handlePhotoApiData($data);
	}
	
	/**
	 * @param $data
	 * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
	 */
	private function handlePhotoApiData($data)
	{
		// Parsing the API response
		$status = (int)data_get($data, 'status');
		$message = data_get($data, 'message', t('unknown_error'));
		
		// HTTP Error Found
		if (!data_get($data, 'isSuccessful')) {
			// AJAX Response
			if (isFromAjax()) {
				return ajaxResponse()->json(['error' => $message], $status);
			}
			
			flash($message)->error();
			
			return redirect()->to(url('account'))->withInput();
		}
		
		// AJAX Response
		if (isFromAjax()) {
			if (!data_get($data, 'success')) {
				return ajaxResponse()->json(['error' => $message], $status);
			}
			
			$fileInput = data_get($data, 'extra.fileInput');
			
			return ajaxResponse()->json($fileInput);
		}
		
		// Notification Message
		if (data_get($data, 'success')) {
			flash($message)->success();
		} else {
			flash($message)->error();
		}
		
		return redirect()->to(url('account'));
	}
}
