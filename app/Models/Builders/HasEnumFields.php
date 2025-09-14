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

namespace App\Models\Builders;

use Illuminate\Support\Facades\DB;

trait HasEnumFields
{
	public static function getPossibleEnumValues(string $column): array
	{
		$instance = new static(); // Create an instance of the model to be able to get the table name
		$connectionName = $instance->getConnection()->getName();
		$table = DB::getTablePrefix() . $instance->getTable();
		
		try {
			$sql = 'SHOW COLUMNS FROM ' . $table . ' WHERE Field = "' . $column . '"';
			$type = DB::connection($connectionName)->select($sql)[0]->Type;
		} catch (\Throwable $e) {
			$type = '';
		}
		
		$enum = [];
		
		if (!empty($type)) {
			preg_match('/^enum\((.*)\)$/', $type, $matches);
			$exploded = explode(',', $matches[1]);
			foreach ($exploded as $value) {
				$enum[] = trim($value, "'");
			}
		}
		
		return $enum;
	}
	
	public static function getEnumValuesAsAssocArray(string $column): array
	{
		$enumValues = static::getPossibleEnumValues($column);
		
		return collect($enumValues)
			->mapWithKeys(fn ($item) => [$item => $item])
			->toArray();
	}
}
