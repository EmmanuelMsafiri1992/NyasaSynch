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

namespace App\Http\Controllers\Web\Public\Post\CreateOrEdit\MultiSteps;

use App\Helpers\Referrer;
use App\Helpers\UrlGen;
use App\Http\Controllers\Api\Payment\RetrievePackageFeatures;
use App\Http\Controllers\Api\Payment\HasPaymentReferrers;
use App\Http\Controllers\Web\Public\Auth\Traits\VerificationTrait;
use App\Http\Controllers\Web\Public\Post\CreateOrEdit\MultiSteps\Traits\WizardTrait;
use App\Http\Requests\Front\PostRequest;
use App\Http\Controllers\Web\Public\FrontController;
use Illuminate\Http\Request;
use Larapen\LaravelMetaTags\Facades\MetaTag;

class EditController extends FrontController
{
	use HasPaymentReferrers;
	use WizardTrait;
	use VerificationTrait;
	use RetrievePackageFeatures;
	
	public $data;
	
	/**
	 * EditController constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		
		$this->commonQueries();
	}
	
	/**
	 * Common Queries
	 *
	 * @return void
	 */
	public function commonQueries(): void
	{
		$this->getPaymentReferrersData();
		
		// References
		$data = [];
		
		// Get postTypes
		$data['postTypes'] = Referrer::getPostTypes($this->cacheExpiration);
		view()->share('postTypes', $data['postTypes']);
		
		// Get Salary Types
		$data['salaryTypes'] = Referrer::getSalaryTypes($this->cacheExpiration);
		view()->share('salaryTypes', $data['salaryTypes']);
		
		// Get the User's Company
		if (auth()->check()) {
			$data['companies'] = Referrer::getUsersCompanies($this->cacheExpiration);
			view()->share('companies', $data['companies']);
		}
		
		// Save common's data
		$this->data = $data;
	}
	
	/**
	 * Show the form
	 *
	 * @param $id
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
	 */
	public function getForm($id, Request $request)
	{
		// Check if the form type is 'Single-Step Form' and make redirection to it (permanently).
		$isSingleStepFormEnabled = (config('settings.listing_form.publication_form_type') == '2');
		if ($isSingleStepFormEnabled) {
			$url = url('edit/' . $id);
			
			return redirect()->to($url, 301)->withHeaders(config('larapen.core.noCacheHeaders'));
		}
		
		$data = [];
		
		// Get Post
		$post = null;
		if (auth()->check()) {
			// Get post - Call API endpoint
			$endpoint = '/posts/' . $id;
			$queryParams = [
				'embed'               => 'category,city,subAdmin1,subAdmin2,company',
				'countryCode'         => config('country.code'),
				'unactivatedIncluded' => 1,
				'belongLoggedUser'    => 1, // Logged user required
				'noCache'             => 1,
			];
			$queryParams = array_merge(request()->all(), $queryParams);
			$data = makeApiRequest('get', $endpoint, $queryParams);
			
			$apiMessage = $this->handleHttpError($data);
			$post = data_get($data, 'result');
		}
		
		abort_if(empty($post), 404, t('post_not_found'));
		
		view()->share('post', $post);
		$this->shareWizardMenu($request, $post);
		
		// Share the post's current active payment info (If exists)
		$this->getCurrentActivePaymentInfo($post);
		
		// Get the Post's Company
		if (!empty(data_get($post, 'company_id'))) {
			view()->share('postCompany', data_get($post, 'company'));
		}
		
		// Get the Post's City's Administrative Division
		$adminType = config('country.admin_type', 0);
		$admin = data_get($post, 'city.subAdmin' . $adminType);
		if (!empty($admin)) {
			view()->share('admin', $admin);
		}
		
		// Meta Tags
		MetaTag::set('title', t('update_my_ad'));
		MetaTag::set('description', t('update_my_ad'));
		
		return appView('post.createOrEdit.multiSteps.edit', $data);
	}
	
	/**
	 * Submit the form
	 *
	 * @param $id
	 * @param \App\Http\Requests\Front\PostRequest $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function postForm($id, PostRequest $request)
	{
		// Add required data in the request for API
		$inputArray = [
			'count_packages'        => $this->countPackages ?? 0,
			'count_payment_methods' => $this->countPaymentMethods ?? 0,
		];
		request()->merge($inputArray);
		
		// Call API endpoint
		$endpoint = '/posts/' . $id;
		$data = makeApiRequest('put', $endpoint, $request->all(), [], true);
		
		// Parsing the API response
		$message = data_get($data, 'message', t('unknown_error'));
		
		// HTTP Error Found
		if (!data_get($data, 'isSuccessful')) {
			flash($message)->error();
			
			if (data_get($data, 'extra.previousUrl')) {
				return redirect()->to(data_get($data, 'extra.previousUrl'))->withInput();
			} else {
				return redirect()->back()->withInput();
			}
		}
		
		// Notification Message
		if (data_get($data, 'success')) {
			flash($message)->success();
		} else {
			flash($message)->error();
		}
		
		// Get Post Resource
		$post = data_get($data, 'result');
		
		// Get the next URL
		if (data_get($data, 'extra.steps.payment')) {
			$nextUrl = url('posts/' . $id . '/payment');
		} else {
			$nextUrl = UrlGen::post($post);
		}
		
		// Get Post Resource
		$post = data_get($data, 'result');
		
		if (
			data_get($data, 'extra.sendEmailVerification.emailVerificationSent')
			|| data_get($data, 'extra.sendPhoneVerification.phoneVerificationSent')
		) {
			session()->put('itemNextUrl', $nextUrl);
			
			if (data_get($data, 'extra.sendEmailVerification.emailVerificationSent')) {
				session()->put('emailVerificationSent', true);
				
				// Show the Re-send link
				$this->showReSendVerificationEmailLink($post, 'posts');
			}
			
			if (data_get($data, 'extra.sendPhoneVerification.phoneVerificationSent')) {
				session()->put('phoneVerificationSent', true);
				
				// Show the Re-send link
				$this->showReSendVerificationSmsLink($post, 'posts');
				
				// Phone Number verification
				// Get the token|code verification form page URL
				// The user is supposed to have received this token|code by SMS
				$nextUrl = url('posts/verify/phone/');
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
}
