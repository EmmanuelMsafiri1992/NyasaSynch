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

use App\Helpers\DBTool;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

trait DBConnectionFailedExceptionHandler
{
	/**
	 * Test Database Connection
	 *
	 * @return bool
	 */
	private function isDBConnectionFailedException(): bool
	{
		$pdo = null;
		
		try {
			$pdo = DBTool::getPdoConnection();
		} catch (\Throwable $e) {
		}
		
		return (appInstallFilesExist() && !($pdo instanceof \PDO));
	}
	
	/**
	 * @param \Throwable $e
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
	 */
	protected function responseDBConnectionFailedException(\Throwable $e, Request $request): Response|JsonResponse
	{
		$message = $this->getDBConnectionFailedExceptionMessage($e, $request);
		
		return $this->responseCustomError($e, $request, $message);
	}
	
	// PRIVATE
	
	/**
	 * @param \Throwable $e
	 * @param \Illuminate\Http\Request $request
	 * @return string
	 */
	private function getDBConnectionFailedExceptionMessage(\Throwable $e, Request $request): string
	{
		$message = 'Connection to the database failed.';
		if (!empty($e->getMessage())) {
			$message .= "\n" . $e->getMessage();
		}
		
		return $message;
	}
}
