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

use App\Exceptions\Handler\Plugin\OutToDatePlugin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/*
 * Check if there are no problems in a plugin code
 */

trait PluginTypeDeclarationsExceptionHandler
{
	use OutToDatePlugin;
	
	/**
	 * @param \Throwable $e
	 * @return bool
	 */
	protected function isPluginTypeDeclarationsException(\Throwable $e): bool
	{
		// Check if there are no problems in a plugin code
		return (
			method_exists($e, 'getFile') && method_exists($e, 'getMessage')
			&& !empty($e->getFile()) && !empty($e->getMessage())
			&& str_contains($e->getFile(), '/extras/plugins/')
			&& str_contains($e->getMessage(), 'extras\plugins\\')
			&& str_contains($e->getMessage(), 'must be compatible')
		);
	}
	
	/**
	 * @param \Throwable $e
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
	 */
	protected function responsePluginTypeDeclarationsException(\Throwable $e, Request $request): Response|JsonResponse
	{
		$message = $this->getPluginTypeDeclarationsExceptionMessage($e, $request);
		
		return $this->responseCustomError($e, $request, $message);
	}
	
	// PRIVATE
	
	/**
	 * @param \Throwable $e
	 * @param \Illuminate\Http\Request $request
	 * @return string|null
	 */
	private function getPluginTypeDeclarationsExceptionMessage(\Throwable $e, Request $request): ?string
	{
		$message = $e->getMessage();
		
		return !empty($message) ? $this->tryToArchivePlugin($message) : null;
	}
}
