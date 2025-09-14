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

namespace App\Helpers\Categories\Traits;

use App\Helpers\DBTool;

trait IndexesTrait
{
	/**
	 * Create the Nested Set indexes
	 *
	 * @return void
	 * @throws \App\Exceptions\Custom\CustomException
	 */
	public function createNestedSetIndexes(): void
	{
		$this->checkTablesAndColumns();
		
		// Make the 'lft' & 'rgt' columns unique and index the 'depth' column
		
		// Check if a unique indexes key exist, and drop it.
		DBTool::dropIndexIfExists($this->nestedTable, 'lft');
		DBTool::dropIndexIfExists($this->nestedTable, 'rgt');
		DBTool::dropIndexIfExists($this->nestedTable, 'depth');
		
		// Create indexes
		DBTool::createIndexIfNotExists($this->nestedTable, 'lft'); // Should be unique
		DBTool::createIndexIfNotExists($this->nestedTable, 'rgt'); // Should be unique
		DBTool::createIndexIfNotExists($this->nestedTable, 'depth');
	}
}
