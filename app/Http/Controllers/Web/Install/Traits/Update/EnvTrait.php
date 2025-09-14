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

namespace App\Http\Controllers\Web\Install\Traits\Update;

use App\Helpers\DotenvEditor;

trait EnvTrait
{
	/**
	 * Update the current version to last version
	 *
	 * @param $last
	 * @throws \App\Exceptions\Custom\CustomException
	 */
	private function setCurrentVersion($last): void
	{
		DotenvEditor::setKey('APP_VERSION', $last);
		DotenvEditor::save();
	}
}
