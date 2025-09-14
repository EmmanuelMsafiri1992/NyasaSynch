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

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait RawIndexTrait
{
	/**
	 * Get full index name powered by Laravel
	 *
	 * @param string $table
	 * @param string $index
	 * @param string $type
	 * @return string
	 */
	public static function getRawIndexName(string $table, string $index, string $type = 'index'): string
	{
		$prefix = self::getRawTablePrefix();
		
		return $prefix . $table . '_' . $index . '_' . $type;
	}
	
	/**
	 * Check if index exists (Raw SQL)
	 *
	 * @param string $tableName
	 * @param string $indexName
	 * @param string $type
	 * @return bool
	 */
	public static function rawDoesIndexExist(string $tableName, string $indexName, string $type = 'index'): bool
	{
		$isMariaDb = self::isMariaDB();
		if ($isMariaDb) {
			return self::doesMariaDBIndexExist($tableName, $indexName, $type);
		} else {
			return self::doesMySQLIndexExist($tableName, $indexName, $type);
		}
	}
	
	/**
	 * Check if MySQL index exists (Raw SQL)
	 *
	 * @param string $tableName
	 * @param string $indexName
	 * @param string $type
	 * @return bool
	 */
	public static function doesMySQLIndexExist(string $tableName, string $indexName, string $type = 'index'): bool
	{
		$tableNameWithPrefix = DB::getTablePrefix() . $tableName;
		$idxDb = DB::connection()->getDatabaseName();
		
		$sql = [
			'Key_name'   => 'SHOW INDEX FROM `' . $tableNameWithPrefix . '` FROM `' . $idxDb . '`;',
			'INDEX_NAME' => 'SELECT DISTINCT INDEX_NAME
						 FROM `INFORMATION_SCHEMA`.`STATISTICS`
						 WHERE `TABLE_SCHEMA` = \'' . $idxDb . '\'
							AND `TABLE_NAME` = \'' . $tableNameWithPrefix . '\'',
		];
		
		// Exception for MySQL 8
		$isMySql8OrGreater = (!self::isMariaDB() && self::isMySqlMinVersion('8.0'));
		$indexColumn = $isMySql8OrGreater ? 'INDEX_NAME' : 'Key_name';
		
		$results = DB::select($sql[$indexColumn]);
		
		if (is_array($results) && count($results) > 0) {
			$results = collect($results)->mapWithKeys(function ($item) use ($indexColumn) {
				$indexNameLocal = $item->{$indexColumn} ?? null;
				
				return [$indexNameLocal => $indexNameLocal];
			})->toArray();
			
			return in_array($indexName, $results);
		}
		
		return false;
	}
	
	/**
	 * Check if MariaDB index exists (Raw SQL)
	 *
	 * @param string $tableName
	 * @param string $indexName
	 * @param string $type
	 * @return bool
	 */
	public static function doesMariaDBIndexExist(string $tableName, string $indexName, string $type = 'index'): bool
	{
		$tableNameWithPrefix = DB::getTablePrefix() . $tableName;
		$idxDb = DB::connection()->getDatabaseName();
		
		$sql = 'show indexes from `' . $tableNameWithPrefix . '` in `' . $idxDb . '`;';
		$results = DB::select($sql);
		
		if (is_array($results) && count($results) > 0) {
			$results = collect($results)->mapWithKeys(function ($item) {
				$indexNameLocal = $item->Key_name ?? null;
				
				return [$indexNameLocal => $indexNameLocal];
			})->toArray();
			
			return in_array($indexName, $results);
		}
		
		return false;
	}
	
	/**
	 * Create (unique) index if not exists (Raw SQL)
	 *
	 * @param string $tableName
	 * @param string $indexName
	 * @param string $type
	 * @param bool $canValidateIndexColumn
	 * @return void
	 */
	public static function rawCreateIndexIfNotExists(
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
		
		if (self::rawDoesIndexExist($tableName, $indexName, $type)) {
			return;
		}
		
		$tableNameWithPrefix = DB::getTablePrefix() . $tableName;
		$indexNameInLaravel = self::getRawIndexName($tableName, $indexName, $type);
		
		if ($type == 'unique') {
			$sql = "ALTER TABLE `" . $tableNameWithPrefix . "` ADD UNIQUE INDEX `" . $indexNameInLaravel . "` (`" . $indexName . "`)";
		} else {
			$sql = "ALTER TABLE `" . $tableNameWithPrefix . "` ADD INDEX `" . $indexNameInLaravel . "` (`" . $indexName . "`)";
		}
		DB::unprepared($sql);
	}
	
	/**
	 * Drop index if exists (Raw SQL)
	 *
	 * @param string $tableName
	 * @param string $indexName
	 * @param string $type
	 * @return void
	 */
	public static function rawDropIndexIfExists(string $tableName, string $indexName, string $type = 'index'): void
	{
		$isMariaDb = self::isMariaDB();
		if ($isMariaDb) {
			self::dropMariaDBIndexIfExists($tableName, $indexName, $type);
		} else {
			self::dropMySQLIndexIfExists($tableName, $indexName, $type);
		}
	}
	
	/**
	 * Drop MySQL index if exists (Raw SQL)
	 *
	 * @param string $tableName
	 * @param string $indexName
	 * @param string $type
	 * @return void
	 */
	public static function dropMySQLIndexIfExists(string $tableName, string $indexName, string $type = 'index'): void
	{
		if (self::doesMySQLIndexExist($tableName, $indexName)) {
			$tableNameWithPrefix = DB::getTablePrefix() . $tableName;
			
			$sql = "ALTER TABLE `" . $tableNameWithPrefix . "` DROP INDEX " . $indexName . ";";
			DB::unprepared($sql);
		}
	}
	
	/**
	 * Drop MariaDB index if exists (Raw SQL)
	 *
	 * @param string $tableName
	 * @param string $indexName
	 * @param string $type
	 * @return void
	 */
	public static function dropMariaDBIndexIfExists(string $tableName, string $indexName, string $type = 'index'): void
	{
		if (self::doesMariaDBIndexExist($tableName, $indexName)) {
			$tableNameWithPrefix = DB::getTablePrefix() . $tableName;
			
			$sql = "DROP INDEX `" . $indexName . "` ON `" . $tableNameWithPrefix . "`;";
			DB::unprepared($sql);
		}
	}
}
