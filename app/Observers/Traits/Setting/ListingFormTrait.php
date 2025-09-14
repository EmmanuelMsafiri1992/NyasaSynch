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

namespace App\Observers\Traits\Setting;

use App\Models\Post;

trait ListingFormTrait
{
	/**
	 * Updating
	 *
	 * @param $setting
	 * @param $original
	 * @return void
	 */
	public function listingFormUpdating($setting, $original)
	{
		$this->autoReviewedExistingPostsIfApprobationIsEnabled($setting);
	}
	
	/**
	 * Auto approve all the existing posts,
	 * If the Posts Approbation feature is enabled
	 *
	 * @param $setting
	 * @return void
	 */
	private function autoReviewedExistingPostsIfApprobationIsEnabled($setting): void
	{
		// Enable Posts Approbation by User Admin (Post Review)
		if (array_key_exists('listings_review_activation', $setting->value)) {
			// If Post Approbation is enabled,
			// then set the reviewed field to "true" for all the existing Posts
			if ((int)$setting->value['listings_review_activation'] == 1) {
				Post::whereNull('reviewed_at')->update(['reviewed_at' => now()]);
			}
		}
	}
}
