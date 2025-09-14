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

use App\Models\Permission;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;

class Admin
{
	/**
	 * Handle an incoming request.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param \Closure $next
	 * @param $guard
	 * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|mixed
	 */
	public function handle(Request $request, Closure $next, $guard = null)
	{
		$message = trans('admin.unauthorized');
		
		if (!auth()->check()) {
			// Block access if user is guest (not logged in)
			if (isFromAjax($request)) {
				return ajaxResponse()->text($message, Response::HTTP_UNAUTHORIZED);
			} else {
				if ($request->path() != admin_uri('login')) {
					notification($message, 'error');
					
					return redirect()->guest(admin_uri('login'));
				}
			}
		} else {
			try {
				$aclTableNames = config('permission.table_names');
				if (isset($aclTableNames['permissions'])) {
					if (!Schema::hasTable($aclTableNames['permissions'])) {
						return $next($request);
					}
				}
			} catch (\Exception $e) {
				return $next($request);
			}
			
			$user = User::query()->count();
			if (!($user == 1)) {
				// If user does //not have this permission
				$authUser = auth()->guard($guard)->user();
				if (!doesUserHavePermission($authUser, Permission::getStaffPermissions())) {
					if (isFromAjax($request)) {
						return ajaxResponse()->text($message, Response::HTTP_UNAUTHORIZED);
					} else {
						auth()->logout();
						notification($message, 'error');
						
						return redirect()->guest(admin_uri('login'));
					}
				}
			}
		}
		
		return $next($request);
	}
}
