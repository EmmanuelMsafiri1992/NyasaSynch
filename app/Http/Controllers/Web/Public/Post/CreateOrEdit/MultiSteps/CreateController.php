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

use App\Helpers\Files\TmpUpload;
use App\Helpers\Referrer;
use App\Helpers\UrlGen;
use App\Http\Controllers\Api\Payment\HasPaymentTrigger;
use App\Http\Controllers\Api\Payment\Promotion\SingleStepPayment;
use App\Http\Controllers\Api\Payment\HasPaymentReferrers;
use App\Http\Controllers\Web\Public\Auth\Traits\VerificationTrait;
use App\Http\Controllers\Web\Public\Payment\HasPaymentRedirection;
use App\Http\Controllers\Web\Public\Post\CreateOrEdit\MultiSteps\Traits\Create\ClearTmpInputTrait;
use App\Http\Controllers\Web\Public\Post\CreateOrEdit\MultiSteps\Traits\Create\SubmitTrait;
use App\Http\Controllers\Web\Public\Post\CreateOrEdit\MultiSteps\Traits\WizardTrait;
use App\Http\Controllers\Web\Public\Post\CreateOrEdit\Traits\PricingPageUrlTrait;
use App\Http\Requests\Front\PackageRequest;
use App\Http\Requests\Front\PostRequest;
use App\Models\Post;
use App\Http\Controllers\Web\Public\FrontController;
use App\Models\Scopes\VerifiedScope;
use App\Models\Scopes\ReviewedScope;
use App\Observers\Traits\PictureTrait;
use Illuminate\Http\Request;
use Larapen\LaravelMetaTags\Facades\MetaTag;

class CreateController extends FrontController
{
	use VerificationTrait;
	use HasPaymentReferrers;
	use WizardTrait;
	use PricingPageUrlTrait;
	use PictureTrait, ClearTmpInputTrait;
	use SubmitTrait;
	use HasPaymentTrigger, SingleStepPayment, HasPaymentRedirection;
	
	protected string $baseUrl = '/posts/create';
	protected string $tmpUploadDir = 'temporary';
	
	/**
	 * CreateController constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		
		$this->commonQueries();

		$this->baseUrl = url($this->baseUrl);

		// Apply middleware
		$this->applyMiddleware();
	}
	
	/**
	 * Apply middleware to controller actions.
	 */
	protected function applyMiddleware(): void
	{
		// Check if guests can post Listings
		if (config('settings.listing_form.guest_can_submit_listings') != '1') {
			$this->middleware('auth');
		}
	}
	
	/**
	 * @return void
	 */
	public function commonQueries(): void
	{
		$this->getPaymentReferrersData();
		$this->setPaymentSettingsForPromotion();
		
		// Get postTypes
		$postTypes = Referrer::getPostTypes($this->cacheExpiration);
		view()->share('postTypes', $postTypes);
		
		// Get Salary Types
		$salaryTypes = Referrer::getSalaryTypes($this->cacheExpiration);
		view()->share('salaryTypes', $salaryTypes);
		
		$companies = collect();
		if (auth()->check()) {
			// Get all the User's Companies
			$companies = Referrer::getUsersCompanies($this->cacheExpiration);
			$companies = collect($companies);
			
			// Get the User's latest Company
			if ($companies->has(0)) {
				$postCompany = $companies->get(0);
				view()->share('postCompany', $postCompany);
			}
		}
		$postInput = request()->session()->get('postInput');
		if (isset($postInput['company'], $postInput['company']['name'])) {
			$companies = $companies->prepend($postInput['company']);
		}
		view()->share('companies', $companies);
		
		// Meta Tags
		[$title, $description, $keywords] = getMetaTag('create');
		MetaTag::set('title', $title);
		MetaTag::set('description', strip_tags($description));
		MetaTag::set('keywords', $keywords);
	}
	
	/**
	 * Checking for the current step
	 *
	 * @param Request $request
	 * @return int
	 */
	public function step(Request $request): int
	{
		if ($request->get('error') == 'paymentCancelled') {
			if ($request->session()->has('postId')) {
				$request->session()->forget('postId');
			}
		}
		
		$postId = $request->session()->get('postId');
		
		$step = 0;
		
		$data = $request->session()->get('postInput');
		if (isset($data) || !empty($postId)) {
			$step = 1;
		} else {
			return $step;
		}
		
		$data = $request->session()->get('paymentInput');
		if (isset($data) || !empty($postId)) {
			$step = 2;
		} else {
			return $step;
		}
		
		return $step;
	}
	
	/**
	 * New Post's Form.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
	 */
	public function getPostStep(Request $request)
	{
		// Check if the 'Pricing Page' must be started first, and make redirection to it.
		$pricingUrl = $this->getPricingPage($this->getSelectedPackage());
		if (!empty($pricingUrl)) {
			return redirect()->to($pricingUrl)->withHeaders(config('larapen.core.noCacheHeaders'));
		}
		
		// Check if the form type is 'Single-Step Form' and make redirection to it (permanently).
		$isSingleStepFormEnabled = (config('settings.listing_form.publication_form_type') == '2');
		if ($isSingleStepFormEnabled) {
			$url = url('create');
			
			return redirect()->to($url, 301)->withHeaders(config('larapen.core.noCacheHeaders'));
		}
		
		// Only Admin users and Employers/Companies can post ads
		if (auth()->check()) {
			$userTypeId = auth()->user()->user_type_id ?? null;
			$isCandidateAccount = ($userTypeId != 1);
			if ($isCandidateAccount) {
				return redirect()->intended('account');
			}
		}
		
		$this->shareWizardMenu($request);
		
		// Create an unique temporary ID
		if (!$request->session()->has('uid')) {
			$request->session()->put('uid', uniqueCode(9));
		}
		
		$postInput = $request->session()->get('postInput');
		
		// Get the next URL button label
		if (
			isset($this->countPackages, $this->countPaymentMethods)
			&& $this->countPackages > 0
			&& $this->countPaymentMethods > 0
		) {
			$nextStepLabel = t('Next');
		} else {
			$nextStepLabel = t('submit');
		}
		view()->share('nextStepLabel', $nextStepLabel);
		
		return appView('post.createOrEdit.multiSteps.create', compact('postInput'));
	}
	
	/**
	 * Store a new Post.
	 *
	 * @param \App\Http\Requests\Front\PostRequest $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function postPostStep(PostRequest $request)
	{
		// Use unique ID to store post's pictures
		if ($request->session()->has('uid')) {
			$this->tmpUploadDir = $this->tmpUploadDir . '/' . $request->session()->get('uid');
		}
		
		$postInputOld = (array)$request->session()->get('postInput');
		$postInput = $request->all();
		
		// Set the company's temporary ID
		if (isset($postInput['company'], $postInput['company']['name'])) {
			$postInput['company']['id'] = 'new';
		}
		
		// Save uploaded file
		$file = $request->file('company.logo');
		if (!empty($file)) {
			$filePath = TmpUpload::image($this->tmpUploadDir, $file);
			$postInput['company']['logo'] = $filePath;
			
			// Remove old company logo
			if (isset($postInputOld['company'], $postInputOld['company']['logo'])) {
				try {
					$this->removePictureWithItsThumbs($postInputOld['company']['logo']);
				} catch (\Throwable $e) {
				}
			}
		} else {
			// Skip old logo if the logo field is not filled
			if (isset($postInputOld['company'], $postInputOld['company']['logo'])) {
				$postInput['company']['logo'] = $postInputOld['company']['logo'];
			}
		}
		
		$request->session()->put('postInput', $postInput);
		
		// Get the next URL
		if (
			isset($this->countPackages, $this->countPaymentMethods)
			&& $this->countPackages > 0
			&& $this->countPaymentMethods > 0
		) {
			$nextUrl = url('posts/create/payment');
			$nextUrl = qsUrl($nextUrl, request()->only(['package']), null, false);
			
			return redirect()->to($nextUrl)->withHeaders(config('larapen.core.noCacheHeaders'));
		} else {
			return $this->storeInputDataInDatabase($request);
		}
	}
	
	/**
	 * Payment's Step
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
	 */
	public function getPaymentStep(Request $request)
	{
		if ($this->step($request) < 1) {
			$backUrl = url($this->baseUrl);
			$backUrl = qsUrl($backUrl, request()->only(['package']), null, false);
			
			return redirect()->to($backUrl)->withHeaders(config('larapen.core.noCacheHeaders'));
		}
		
		// Check if the 'Pricing Page' must be started first, and make redirection to it.
		$pricingUrl = $this->getPricingPage($this->getSelectedPackage());
		if (!empty($pricingUrl)) {
			return redirect()->to($pricingUrl)->withHeaders(config('larapen.core.noCacheHeaders'));
		}
		
		$this->shareWizardMenu($request);
		
		$payment = $request->session()->get('paymentInput');
		
		return appView('post.createOrEdit.multiSteps.packages.create', compact('payment'));
	}
	
	/**
	 * Payment's Step (POST)
	 *
	 * @param \App\Http\Requests\Front\PackageRequest $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function postPaymentStep(PackageRequest $request)
	{
		if ($this->step($request) < 1) {
			$backUrl = url($this->baseUrl);
			$backUrl = qsUrl($backUrl, request()->only(['package']), null, false);
			
			return redirect()->to($backUrl)->withHeaders(config('larapen.core.noCacheHeaders'));
		}
		
		$request->session()->put('paymentInput', $request->validated());
		
		return $this->storeInputDataInDatabase($request);
	}
	
	/**
	 * Confirmation
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
	 */
	public function finish(Request $request)
	{
		if (!session()->has('message')) {
			return redirect()->to('/')->withHeaders(config('larapen.core.noCacheHeaders'));
		}
		
		// Clear the step wizard
		if (session()->has('postId')) {
			// Get the Post
			$post = Post::query()
				->withoutGlobalScopes([VerifiedScope::class, ReviewedScope::class])
				->where('id', session('postId'))
				->first();
			
			abort_if(empty($post), 404, t('post_not_found'));
			
			session()->forget('postId');
		}
		
		// Redirect to the Post,
		// - If User is logged
		// - Or if Email and Phone verification option is not activated
		if (auth()->check() || (config('settings.mail.email_verification') != 1 && config('settings.sms.phone_verification') != 1)) {
			if (!empty($post)) {
				flash(session('message'))->success();
				
				return redirect()->to(UrlGen::postUri($post))->withHeaders(config('larapen.core.noCacheHeaders'));
			}
		}
		
		// Meta Tags
		MetaTag::set('title', session('message'));
		MetaTag::set('description', session('message'));
		
		return appView('post.createOrEdit.multiSteps.finish');
	}
}
