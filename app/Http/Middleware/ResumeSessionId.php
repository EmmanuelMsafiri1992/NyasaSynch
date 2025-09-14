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

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ResumeSessionId
{
	/**
	 * Resume a saved session from an external referrer
	 *
	 * The session()->save() function needs to be called just before adding
	 * the '?sessionId=' . session()->getId() string to the external URL
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param \Closure $next
	 * @return mixed
	 */
	public function handle(Request $request, Closure $next)
	{
		// Get the session ID from the query parameters
		if ($request->filled('sessionId')) {
			$sessionId = getAsStringOrNull($request->input('sessionId'));
			
			// Resume the session
			if (!empty($sessionId)) {
				session()->setId($sessionId);
				session()->start();
			}
		}
		
		return $next($request);
	}
}
