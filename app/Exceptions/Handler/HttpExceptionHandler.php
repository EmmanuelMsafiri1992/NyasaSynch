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

namespace App\Exceptions\Handler;

use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

trait HttpExceptionHandler
{
	/**
	 * Determine if the given exception is an HTTP exception.
	 *
	 * @param \Throwable $e
	 * @return bool
	 */
	protected function isHttpException(\Throwable $e): bool
	{
		return $e instanceof HttpExceptionInterface;
	}
}
