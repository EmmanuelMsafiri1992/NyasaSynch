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

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/*
 * Too Many Connections Exception
 */

trait DBTooManyConnectionsExceptionHandler
{
	/**
	 * @param \Throwable $e
	 * @return bool
	 */
	protected function isDBTooManyConnectionsException(\Throwable $e): bool
	{
		return (
			appInstallFilesExist()
			&& str_contains($e->getMessage(), 'max_user_connections')
			&& str_contains($e->getMessage(), 'active connections')
		);
	}
	
	/**
	 * @param \Throwable $e
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
	 */
	protected function responseDBTooManyConnectionsException(\Throwable $e, Request $request): Response|JsonResponse
	{
		$message = $this->getDBTooManyConnectionsExceptionMessage($e, $request);
		
		return $this->responseCustomError($e, $request, $message);
	}
	
	// PRIVATE
	
	/**
	 * @param \Throwable $e
	 * @param \Illuminate\Http\Request $request
	 * @return string
	 */
	private function getDBTooManyConnectionsExceptionMessage(\Throwable $e, Request $request): string
	{
		// Too many connections
		$message = 'We are currently receiving a large number of connections. ';
		$message .= 'Please try again later. We apologize for the inconvenience.';
		
		return $message;
	}
}
