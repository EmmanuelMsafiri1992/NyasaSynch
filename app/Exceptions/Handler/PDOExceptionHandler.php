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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

trait PDOExceptionHandler
{
	/**
	 * Is a PDO Exception
	 *
	 * @param \Throwable $e
	 * @return bool
	 */
	protected function isPDOException(\Throwable $e): bool
	{
		if (
			($e instanceof \PDOException)
			|| $e->getCode() == 1045
			|| str_contains($e->getMessage(), 'SQLSTATE')
			|| str_contains($e->getFile(), 'Database/Connectors/Connector.php')
		) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * @param \Throwable $e
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
	 */
	protected function responsePDOException(\Throwable $e, Request $request): Response|JsonResponse|RedirectResponse
	{
		// Check if the app installation files exist,
		// to prevent any DB error (from the Admin Panel) when the app is not installed yet.
		if (!appInstallFilesExist()) {
			if ($request->input('exception') != 'PDO') {
				$message = $e->getMessage();
				if (!empty($message)) {
					return $this->responseCustomError($e, $request, $message);
				}
				
				$this->clearLog();
				
				return redirect()->to(getRawBaseUrl() . '/install?exception=PDO');
			}
		}
		
		return $this->responseCustomError($e, $request);
	}
}
