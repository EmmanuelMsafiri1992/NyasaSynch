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
 * Memory is full exception
 * Note: Called only when reporting some Laravel error traces
 */

trait FullMemoryExceptionHandler
{
	/**
	 * @param \Throwable $e
	 * @return bool
	 */
	protected function isFullMemoryException(\Throwable $e): bool
	{
		return (
			str_contains($e->getMessage(), 'Allowed memory size of')
			&& str_contains($e->getMessage(), 'tried to allocate')
		);
	}
	
	/**
	 * @param \Throwable $e
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
	 */
	protected function responseFullMemoryException(\Throwable $e, Request $request): Response|JsonResponse
	{
		$message = $this->getFullMemoryExceptionMessage($e, $request);
		
		return $this->responseCustomError($e, $request, $message);
	}
	
	// PRIVATE
	
	/**
	 * @param \Throwable $e
	 * @param \Illuminate\Http\Request $request
	 * @return string
	 */
	private function getFullMemoryExceptionMessage(\Throwable $e, Request $request): string
	{
		// Memory is full
		$message = $e->getMessage() . ". \n";
		$message .= 'The server\'s memory must be increased so that it can support the load of the requested resource.';
		
		return $message;
	}
}
