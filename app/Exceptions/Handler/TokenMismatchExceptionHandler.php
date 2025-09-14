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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Session\TokenMismatchException;

/*
 * Token Mismatch Exception (Deprecated)
 * Replaced by the "Authentication Timeout Exception"
 */

trait TokenMismatchExceptionHandler
{
	/**
	 * @param \Throwable $e
	 * @return bool
	 */
	protected function isTokenMismatchException(\Throwable $e): bool
	{
		return ($e instanceof TokenMismatchException);
	}
	
	/**
	 * @param \Throwable $e
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
	 */
	protected function responseTokenMismatchException(\Throwable $e, Request $request): Response|JsonResponse|RedirectResponse
	{
		$message = $this->getTokenMismatchExceptionMessage($e, $request);
		
		if (!isFromApi($request) && !isFromAjax($request)) {
			$previousUrl = $this->getTokenMismatchExceptionPreviousUrl();
			if (!empty($previousUrl)) {
				notification($message, 'error');
				
				return redirect()->to($previousUrl)->withInput();
			}
		}
		
		return $this->responseCustomError($e, $request, $message, Response::HTTP_UNAUTHORIZED);
	}
	
	// PRIVATE
	
	/**
	 * @param \Throwable $e
	 * @param \Illuminate\Http\Request $request
	 * @return string
	 */
	private function getTokenMismatchExceptionMessage(\Throwable $e, Request $request): string
	{
		return t('session_expired_reload_needed');
	}
	
	/**
	 * @return string|null
	 */
	private function getTokenMismatchExceptionPreviousUrl(): ?string
	{
		$previousUrl = url()->previous();
		
		$param = 'error=CsrfToken';
		if (!str_contains($previousUrl, $param)) {
			$queryString = (parse_url($previousUrl, PHP_URL_QUERY) ? '&' : '?') . $param;
			
			return $previousUrl . $queryString;
		}
		
		return null;
	}
}
