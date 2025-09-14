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

use App\Helpers\UrlGen;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

trait AuthenticationExceptionHandler
{
	/**
	 * @param \Throwable $e
	 * @return bool
	 */
	protected function isAuthenticationException(\Throwable $e): bool
	{
		return ($e instanceof AuthenticationException);
	}
	
	/**
	 * @param \Throwable $e
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
	 */
	protected function responseAuthenticationException(\Throwable $e, Request $request): Response|JsonResponse|RedirectResponse
	{
		$message = t('unauthenticated_or_token_expired');
		
		if (!isFromApi($request) && !isFromAjax($request)) {
			notification($message, 'error');
			
			return redirect()->guest(UrlGen::loginPath());
		}
		
		return $this->responseCustomError($e, $request, $message, Response::HTTP_UNAUTHORIZED);
	}
}
