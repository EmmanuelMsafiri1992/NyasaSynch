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

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

// install
Route::namespace('Install')->group(__DIR__ . '/web/install.php');

// Storage file redirect for logo images
Route::get('storage/app/default/{filename}', function ($filename) {
    $publicPath = public_path('app/default/' . $filename);
    if (file_exists($publicPath)) {
        return response()->file($publicPath);
    }
    abort(404);
});

Route::middleware(['installed'])
	->group(function () {
		// admin
		$prefix = config('larapen.admin.route', 'admin');
		Route::namespace('Admin')->prefix($prefix)->group(__DIR__ . '/web/admin.php');
		
		// public
		Route::namespace('Public')->group(__DIR__ . '/web/public.php');
	});
