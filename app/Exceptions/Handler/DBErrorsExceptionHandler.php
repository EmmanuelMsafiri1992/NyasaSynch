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
 * DB Errors Exception
 */

trait DBErrorsExceptionHandler
{
	/**
	 * Check if it is a DB connection exception
	 *
	 * DB Connection Error:
	 * http://dev.mysql.com/doc/refman/5.7/en/error-messages-server.html
	 *
	 * @param \Throwable $e
	 * @return bool
	 */
	protected function isDBErrorsException(\Throwable $e): bool
	{
		$databaseErrorCodes = [
			'mysql'        => ['1042', '1044', '1045', '1046', '1049'],
			'standardized' => ['08S01', '42000', '28000', '3D000', '42000', '42S22'],
		];
		
		return (
			$this->isPDOException($e)
			&& (
				in_array($e->getCode(), $databaseErrorCodes['mysql'])
				|| in_array($e->getCode(), $databaseErrorCodes['standardized'])
			)
		);
	}
	
	/**
	 * @param \Throwable $e
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
	 */
	protected function responseDBErrorsException(\Throwable $e, Request $request): Response|JsonResponse
	{
		return $this->responseCustomError($e, $request);
	}
}
