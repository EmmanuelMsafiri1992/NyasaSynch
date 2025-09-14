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

namespace App\Http\Controllers\Web\Public\Post\CreateOrEdit\Traits;

use App\Helpers\UrlGen;

trait PricingPageUrlTrait
{
	/**
	 * Check if the Package selection is required and Get the Pricing Page URL
	 *
	 * @param $package
	 * @return string|null
	 */
	public function getPricingPage($package): ?string
	{
		$pricingUrl = null;
		
		// Check if the 'Pricing Page' must be started first, and make redirection to it.
		if (config('settings.listing_form.pricing_page_enabled') == '1') {
			if (empty($package)) {
				$from = '?from=' . request()->path();
				
				$authUser = auth()->check() ? auth()->user() : null;
				if (!empty($authUser)) {
					/*
					 * If the user doesn't have any valid subscription,
					 * Force the user to select a package (on the pricing page) to allow him to create new listing
					 *
					 * IMPORTANT:
					 * To avoid excessive memory consumption that could degrade the application performance,
					 * checking the limitation of the number of listings linked to the users' subscription
					 * will be done downstream (when trying to publish new listings).
					 */
					$authUser->loadMissing('payment');
					if (empty($authUser->payment)) {
						$pricingUrl = UrlGen::pricing() . $from;
					}
				} else {
					// Force the guest to select a package (on the pricing page) to allow him to create new listing
					$pricingUrl = UrlGen::pricing() . $from;
				}
			}
		}
		
		return $pricingUrl;
	}
}
