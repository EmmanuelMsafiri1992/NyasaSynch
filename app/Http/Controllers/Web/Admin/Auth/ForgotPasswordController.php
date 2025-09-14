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

namespace App\Http\Controllers\Web\Admin\Auth;

use App\Http\Controllers\Web\Admin\Controller;

class ForgotPasswordController extends Controller
{
	/**
	 * Create a new controller instance.
	 */
	public function __construct()
	{
		$this->middleware('guest');
	}
	
	// -------------------------------------------------------
	// Laravel overwrites for loading admin views
	// -------------------------------------------------------
	
	/**
	 * Display the form to request a password reset link.
	 * NOTE: Not used with this admin theme.
	 *
	 * @return \Illuminate\Contracts\View\View
	 */
	public function showLinkRequestForm()
	{
		return appView('admin.auth.passwords.email', ['title' => trans('admin.reset_password')]);
	}
}
