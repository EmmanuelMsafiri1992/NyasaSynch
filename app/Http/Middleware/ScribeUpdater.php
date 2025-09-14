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
use Illuminate\Support\Facades\File;

class ScribeUpdater
{
	/**
	 * Handle an incoming request.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param \Closure $next
	 * @return mixed
	 */
	public function handle(Request $request, Closure $next)
	{
		if ($request->segment(1) == 'docs' && $request->segment(2) == 'api') {
			$baseUrl = getAsStringOrNull(url('/'));
			$appApiToken = getAsStringOrNull(config('larapen.core.api.token'));
			
			$this->updateScribeViewFile($baseUrl, $appApiToken);
		}
		
		return $next($request);
	}
	
	/**
	 * Update the Scribe (API docs) view file
	 *
	 * @param string|null $baseUrl
	 * @param string|null $appApiToken
	 * @return void
	 */
	private function updateScribeViewFile(?string $baseUrl = null, ?string $appApiToken = null): void
	{
		if (isDemoDomain()) {
			return;
		}
		
		try {
			$path = resource_path('views/scribe/index.blade.php');
			
			$buffer = null;
			if (File::exists($path)) {
				$buffer = File::get($path);
			}
			
			if (!empty($buffer)) {
				/*
				 * Update the Scribe's base URL JS variable value in the Scribe view file
				 * Examples:
				 * - In new version: var tryItOutBaseUrl = "website-url";
				 * - In old version: var baseUrl = "website-url";
				 */
				$this->updateScribeAppUrl($path, $buffer, $baseUrl, 'tryItOutBaseUrl');
				$this->updateScribeAppUrl($path, $buffer, $baseUrl, 'baseUrl');
				
				// Update the API Token value in the 'try it out' calls
				// in the Scribe view file, to use the current website own API Token
				$this->updateScribeAppApiToken($path, $buffer, $appApiToken);
				
				unset($buffer);
			}
		} catch (\Throwable $e) {
		}
	}
	
	/**
	 * Update the Scribe's base URL JS variable value
	 *
	 * @param string $path
	 * @param string|null $buffer
	 * @param string|null $baseUrl
	 * @param string|null $scribeJsBaseUrlVarName
	 * @return void
	 */
	private function updateScribeAppUrl(
		string  $path,
		?string &$buffer,
		?string $baseUrl = null,
		?string $scribeJsBaseUrlVarName = null
	): void
	{
		if (empty($buffer) || !is_string($buffer)) {
			return;
		}
		if (empty($baseUrl)) {
			$baseUrl = url('/');
		}
		if (empty($scribeJsBaseUrlVarName)) {
			$scribeJsBaseUrlVarName = 'tryItOutBaseUrl';
		}
		
		try {
			$pattern = '|var\s+' . $scribeJsBaseUrlVarName . '\s+=\s+"([^"]+)"|i';
			preg_match($pattern, $buffer, $tmp);
			
			$docBaseUrl = null;
			if (isset($tmp[1])) {
				$docBaseUrl = $tmp[1];
			}
			
			if (!empty($docBaseUrl) && $docBaseUrl != $baseUrl) {
				$pattern = '|var\s+' . $scribeJsBaseUrlVarName . '\s+=\s+"[^"]+"|i';
				$replacement = 'var ' . $scribeJsBaseUrlVarName . ' = "' . $baseUrl . '"';
				$buffer = preg_replace($pattern, $replacement, $buffer);
				
				File::replace($path, $buffer);
			}
		} catch (\Throwable $e) {
		}
	}
	
	/**
	 * Update the API Token value in the 'try it out' calls
	 *
	 * @param string $path
	 * @param string|null $buffer
	 * @param string|null $appApiToken
	 * @return void
	 */
	private function updateScribeAppApiToken(string $path, ?string &$buffer, ?string $appApiToken = null): void
	{
		if (empty($buffer) || !is_string($buffer)) {
			return;
		}
		if (empty($appApiToken)) {
			$appApiToken = config('larapen.core.api.token');
		}
		
		try {
			preg_match_all('|"X-AppApiToken":"[^"]*"|i', $buffer, $tmp, PREG_PATTERN_ORDER);
			
			if (isset($tmp[0])) {
				// Get a 'X-AppApiToken' value from the API doc view
				$docAppApiToken = null;
				if (isset($tmp[0][0])) {
					preg_match('|"X-AppApiToken":"([^"]*)"|i', $tmp[0][0], $tmpToken);
					if (isset($tmpToken[1])) {
						$docAppApiToken = $tmpToken[1];
					}
				}
				
				// Check if all values in array are the same
				$allValuesAreTheSame = (count(array_unique($tmp[0])) === 1);
				if (!$allValuesAreTheSame || $docAppApiToken != $appApiToken) {
					$pattern = '|"X-AppApiToken":"[^"]*"|i';
					$replacement = '"X-AppApiToken":"' . $appApiToken . '"';
					$buffer = preg_replace($pattern, $replacement, $buffer);
					
					File::replace($path, $buffer);
				}
			}
		} catch (\Throwable $e) {
		}
	}
}
