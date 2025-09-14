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

use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/*
 * DB Query Exception
 */

trait DBQueryExceptionHandler
{
	/**
	 * @param \Throwable $e
	 * @return bool
	 */
	protected function isDBQueryException(\Throwable $e): bool
	{
		return ($e instanceof QueryException);
	}
	
	/**
	 * @param \Throwable $e
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
	 */
	protected function responseDBQueryException(\Throwable $e, Request $request): Response|JsonResponse
	{
		$message = $this->getDBQueryExceptionMessage($e, $request);
		
		return $this->responseCustomError($e, $request, $message);
	}
	
	// PRIVATE
	
	/**
	 * @param \Throwable $e
	 * @param \Illuminate\Http\Request $request
	 * @return string
	 */
	private function getDBQueryExceptionMessage(\Throwable $e, Request $request): string
	{
		$message = 'There was issue with the query.';
		
		if (!empty($e->getMessage())) {
			$message .= "\n" . $e->getMessage();
		}
		
		return $message;
	}
}
