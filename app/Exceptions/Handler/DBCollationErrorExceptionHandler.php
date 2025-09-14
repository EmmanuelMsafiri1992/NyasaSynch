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
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/*
 * DB Collation Error Exception
 */

trait DBCollationErrorExceptionHandler
{
	/**
	 * Check if it is a DB collation error exception
	 *
	 * @param \Throwable $e
	 * @return bool
	 */
	protected function isDBCollationErrorException(\Throwable $e): bool
	{
		$message = mb_strtolower($e->getMessage());
		
		return (
			$this->isPDOException($e)
			&& str_contains($message, 'collation')
		);
	}
	
	/**
	 * @param \Throwable $e
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
	 */
	protected function responseDBCollationErrorException(\Throwable $e, Request $request): Response|JsonResponse
	{
		$message = $this->getDBCollationErrorExceptionMessage($e, $request);
		
		return $this->responseCustomError($e, $request, $message);
	}
	
	// PRIVATE
	
	/**
	 * @param \Throwable $e
	 * @param \Illuminate\Http\Request $request
	 * @return string
	 */
	private function getDBCollationErrorExceptionMessage(\Throwable $e, Request $request): string
	{
		$message = $e->getMessage() . ".";
		$message .= "\n\n";
		$message .= 'The database server <strong>character set</strong> and <strong>collation</strong> are not properly configured.';
		// $message .= ' Please visit the "Admin panel → System → System Info" for more information.';
		
		return $message;
	}
}
