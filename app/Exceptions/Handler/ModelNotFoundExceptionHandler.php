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

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/*
 * Model Not Found Exception
 */

trait ModelNotFoundExceptionHandler
{
	/**
	 * @param \Throwable $e
	 * @return bool
	 */
	protected function isModelNotFoundException(\Throwable $e): bool
	{
		return ($e instanceof ModelNotFoundException);
	}
	
	/**
	 * @param \Throwable $e
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
	 */
	protected function responseModelNotFoundException(\Throwable $e, Request $request): Response|JsonResponse
	{
		$message = $this->getModelNotFoundExceptionMessage($e, $request);
		
		return $this->responseCustomError($e, $request, $message, 404);
	}
	
	// PRIVATE
	
	/**
	 * @param \Throwable $e
	 * @param \Illuminate\Http\Request $request
	 * @return string
	 */
	private function getModelNotFoundExceptionMessage(\Throwable $e, Request $request): string
	{
		$message = null;
		
		if (method_exists($e, 'getModel')) {
			$message = 'Entry for ' . str_replace('App\\', '', $e->getModel()) . ' not found.';
		}
		if (!empty($e->getMessage())) {
			$message .= !empty($message) ? "\n" : '';
			$message .= $e->getMessage();
		}
		
		return $message;
	}
}
