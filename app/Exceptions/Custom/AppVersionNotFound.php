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

namespace App\Exceptions\Custom;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class AppVersionNotFound extends Exception
{
	/**
	 * Report the exception.
	 */
	public function report(): void
	{
		Log::warning($this->getMessage());
	}
	
	/**
	 * Render the exception into an HTTP response.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
	 */
	public function render(Request $request): Response|JsonResponse
	{
		$message = $this->getMessage();
		$status = 428; // Precondition Required
		
		if (isFromApi($request) || isFromAjax($request)) {
			$data = [
				'success'   => false,
				'message'   => strip_tags($message),
				'exception' => $this,
			];
			
			return apiResponse()->json($data, $status);
		}
		
		$message = "<strong style='color:red;'>ERROR:</strong> " . $message . "\n\n";
		$message .= "<strong style='color:green;'>SOLUTION:</strong>" . "\n";
		$message .= "1. You have to add in the '/.env' file a line like: <code>APP_VERSION=X.X.X</code>" . "\n";
		$message .= " (Don't forget to replace <code>X.X.X</code> by your current version)" . "\n";
		$message .= "2. (Optional) If you forget your current version, you have to see it from your backup 'config/app.php' file";
		$message .= " (it's the last element of the array)." . "\n";
		$message .= "3. And <strong>refresh this page</strong> to finish upgrading";
		
		$data = [
			'message'   => $message,
			'status'    => $status,
			'exception' => $this,
		];
		
		return response()->view('errors.custom', $data, $status);
	}
}
