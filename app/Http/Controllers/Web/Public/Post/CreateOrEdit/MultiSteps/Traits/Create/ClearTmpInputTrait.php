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

namespace App\Http\Controllers\Web\Public\Post\CreateOrEdit\MultiSteps\Traits\Create;

trait ClearTmpInputTrait
{
	/**
	 * Clear Temporary Inputs & Files
	 */
	public function clearTemporaryInput(): void
	{
		if (session()->has('postInput')) {
			$postInput = (array)session('postInput');
			if (isset($postInput['company'], $postInput['company']['logo'])) {
				$filePath = $postInput['company']['logo'];
				try {
					$this->removePictureWithItsThumbs($filePath);
				} catch (\Throwable $e) {
					if (!empty($e->getMessage())) {
						flash($e->getMessage())->error();
					}
				}
			}
			session()->forget('postInput');
		}
		
		if (session()->has('paymentInput')) {
			session()->forget('paymentInput');
		}
		
		if (session()->has('uid')) {
			session()->forget('uid');
		}
	}
}
