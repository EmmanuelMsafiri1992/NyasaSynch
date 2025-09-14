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

namespace App\Helpers\DBTool;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait IndexTrait
{
	use RawIndexTrait;
	
	/**
	 * Get full (unique) index name powered by Laravel
	 *
	 * @param string $table
	 * @param string $index
	 * @param string $type
	 * @return string
	 */
	public static function getIndexName(string $table, string $index, string $type = 'index'): string
	{
		$prefix = DB::getTablePrefix();
		
		return $prefix . $table . '_' . $index . '_' . $type;
	}
	
	/**
	 * Check if (unique) index exists (Laravel)
	 * $type can be: index, unique
	 *
	 * @param string $table
	 * @param string $index
	 * @param string $type
	 * @return bool
	 */
	public static function doesIndexExist(string $table, string $index, string $type = 'index'): bool
	{
		if (!Schema::hasTable($table)) {
			return false;
		}
		
		$indexes = Schema::getIndexListing($table);
		
		// Check if manually naming index exists
		$found = in_array($index, $indexes);
		
		// If manually naming index is not found,
		// Check if automatic naming index exists
		if (!$found) {
			$indexNameInLaravel = self::getIndexName($table, $index, $type);
			$found = in_array($indexNameInLaravel, $indexes);
		}
		
		return $found;
	}
	
	/**
	 * Create (unique) index if not exists (Laravel)
	 *
	 * @param string $tableName
	 * @param string $indexName
	 * @param string $type
	 * @param bool $canValidateIndexColumn
	 * @return void
	 */
	public static function createIndexIfNotExists(
		string $tableName,
		string $indexName,
		string $type = 'index',
		bool   $canValidateIndexColumn = true
	): void
	{
		if (!Schema::hasTable($tableName)) {
			return;
		}
		
		if ($canValidateIndexColumn) {
			if (!Schema::hasColumn($tableName, $indexName)) {
				return;
			}
		}
		
		if (self::doesIndexExist($tableName, $indexName, $type)) {
			return;
		}
		
		Schema::table($tableName, function (Blueprint $table) use ($tableName, $indexName, $type) {
			if ($type == 'unique') {
				$table->unique($indexName);                  // Automatic naming unique index
				// $table->unique([$indexName], $indexName); // Manually naming unique index
			} else {
				$table->index($indexName);                  // Automatic naming index
				// $table->index([$indexName], $indexName); // Manually naming index
			}
		});
	}
	
	/**
	 * Drop index if exists (Laravel)
	 *
	 * @param string $tableName
	 * @param string $indexName
	 * @param string $type
	 * @return void
	 */
	public static function dropIndexIfExists(string $tableName, string $indexName, string $type = 'index'): void
	{
		if (!Schema::hasTable($tableName) || !Schema::hasColumn($tableName, $indexName)) {
			return;
		}
		
		if (!self::doesIndexExist($tableName, $indexName, $type)) {
			return;
		}
		
		// Drop automatic naming index with '->dropUnique([$indexName])'
		try {
			Schema::table($tableName, function (Blueprint $table) use ($tableName, $indexName, $type) {
				if ($type == 'unique') {
					$table->dropUnique([$indexName]);
				} else {
					$table->dropIndex([$indexName]);
				}
			});
		} catch (\Throwable $e) {
		}
		
		// Drop custom naming index with '->dropUnique($indexName)'
		try {
			Schema::table($tableName, function (Blueprint $table) use ($tableName, $indexName, $type) {
				if ($type == 'unique') {
					$table->dropUnique($indexName);
				} else {
					$table->dropIndex($indexName);
				}
			});
		} catch (\Throwable $e) {
		}
		
		// If the custom naming index is still not drop, use raw SQL to drop it
		if (self::doesIndexExist($tableName, $indexName, $type)) {
			self::rawDropIndexIfExists($tableName, $indexName);
		}
	}
}
