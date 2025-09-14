<?php
/*
 * JobClass - Job Board Web Application
 * Copyright (c) BeDigit. All Rights Reserved
 *
 * Website: https://laraclassifier.com/jobclass
 * Author: BeDigit | https://bedigit.com
 *
 * LICENSE
 * -------
 * This software is furnished under a license and may be used and copied
 * only in accordance with the terms of such license and with the inclusion
 * of the above copyright notice. If you Purchased from CodeCanyon,
 * Please read the full License from here - https://codecanyon.net/licenses/standard
 */

namespace Larapen\Impersonate\Middleware;

use Closure;
use Illuminate\Http\Request;
use Lab404\Impersonate\Services\ImpersonateManager;

class ProtectFromImpersonation
{
	/**
	 * Handle an incoming request.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param \Closure $next
	 * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|mixed
	 * @throws \Illuminate\Contracts\Container\BindingResolutionException
	 */
	public function handle(Request $request, Closure $next)
	{
		$impersonateManager = app()->make(ImpersonateManager::class);
		
		if ($impersonateManager->isImpersonating()) {
			$message = t('Can not be accessed by an impersonator');
			
			if (isFromAjax($request)) {
				$result = [
					'success' => false,
					'message' => $message,
				];
				
				// Add a specific json attributes for 'bootstrap-fileinput' plugin
				if (
					str_contains(currentRouteAction(), 'EditController@updatePhoto')
					|| str_contains(currentRouteAction(), 'EditController@deletePhoto')
				) {
					// NOTE: 'bootstrap-fileinput' need 'error' (text) element & the optional 'errorkeys' (array) element
					$result['error'] = $message;
				}
				
				return response()->json($result, 200, [], JSON_UNESCAPED_UNICODE);
			} else {
				notification($message, 'error');
				
				return redirect()->back();
			}
		}
		
		return $next($request);
	}
}
