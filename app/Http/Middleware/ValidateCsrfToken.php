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

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class ValidateCsrfToken extends Middleware
{
	/**
	 * Indicates whether the XSRF-TOKEN cookie should be set on the response.
	 *
	 * @var bool
	 */
	protected $addHttpCookie = true;
	
	/**
	 * The URIs that should be excluded from CSRF verification.
	 *
	 * @var array
	 */
	protected $except = [];
	
	/**
	 * Handle an incoming request.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param \Closure $next
	 * @return mixed
	 *
	 * @throws \Illuminate\Session\TokenMismatchException
	 */
	public function handle($request, Closure $next)
	{
		// Exception for all requests when CSRF protection is disabled
		$isCsrfProtectionDisabled = (config('settings.security.csrf_protection') != '1');
		if ($isCsrfProtectionDisabled) {
			$this->except = ['*'];
			
			return parent::handle($request, $next);
		}
		
		// Exception for requests from API documentation
		if (request()->header('X-AppType') == 'docs') {
			$this->except = ['*'];
			
			return parent::handle($request, $next);
		}
		
		// Get the referrer URL
		$referrer = $request->headers->get('referer');
		if (!empty($referrer)) {
			// Extract the host from the referrer URL
			$referrerHost = parse_url($referrer, PHP_URL_HOST);
			
			// Extract the host from the app's URL
			$appUrl = config('app.url');
			$appHost = parse_url($appUrl, PHP_URL_HOST);
			
			// Is it request from an external referrer?
			$isRequestFromExternalReferrer = ($referrerHost !== $appHost);
			if ($isRequestFromExternalReferrer) {
				
				// Get all the payment gateways base URLs (that use callback URL)
				$cashfreeUrl = !empty(config('payment.cashfree.baseUrl')) ? config('payment.cashfree.baseUrl') : 'cashfree.com';
				$flutterwaveUrl = !empty(config('payment.flutterwave.baseUrl')) ? config('payment.flutterwave.baseUrl') : 'flutterwave.com';
				$iyzicoUrl = !empty(config('payment.iyzico.baseUrl')) ? config('payment.iyzico.baseUrl') : 'iyzipay.com';
				$paypalUrl = !empty(config('payment.paypal.baseUrl')) ? config('payment.paypal.baseUrl') : 'paypal.com';
				$paystackUrl = !empty(config('payment.paystack.paymentUrl')) ? config('payment.paystack.paymentUrl') : 'paystack.co';
				$payuUrl = !empty(config('payment.payu.baseUrl')) ? config('payment.payu.baseUrl') : 'payu.com';
				$stripeUrl = !empty(config('payment.stripe.baseUrl')) ? config('payment.stripe.baseUrl') : 'stripe.com';
				$twocheckoutUrl = !empty(config('payment.twocheckout.baseUrl')) ? config('payment.twocheckout.baseUrl') : '2checkout.com';
				
				// Get the allowed hosts as array
				$allowedHosts = [
					$cashfreeUrl, $flutterwaveUrl, $iyzicoUrl, $paypalUrl,
					$paystackUrl, $payuUrl, $stripeUrl, $twocheckoutUrl,
				];
				
				// Check if the referrer host matches any host in the allowed hosts
				$isRequestFromAllowedHosts = collect($allowedHosts)
					->contains(function ($url) use ($referrerHost) {
						if (empty($url) || !is_string($url)) return false;
						
						return str_contains($referrerHost, parse_url($url, PHP_URL_HOST));
					});
				
				// Exception for requests from allowed hosts
				if ($isRequestFromAllowedHosts) {
					$this->except = ['*'];
					
					return parent::handle($request, $next);
				}
			}
		}
		
		// Exception for requests from Resend
		$this->except[] = 'resend/*';
		
		return parent::handle($request, $next);
	}
}
