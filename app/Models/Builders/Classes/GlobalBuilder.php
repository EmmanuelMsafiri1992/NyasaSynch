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

namespace App\Models\Builders\Classes;

use Illuminate\Database\Eloquent\Builder;

class GlobalBuilder extends Builder
{
	public function columnIsEmpty(string $column): static
	{
		$this->where(function (self $query) use ($column) {
			$query->where($column, '')->orWhere($column, 0)->orWhereNull($column);
		});
		
		return $this;
	}
	
	public function columnIsNotEmpty(string $column): static
	{
		$this->where(function (self $query) use ($column) {
			$query->where($column, '!=', '')->where($column, '!=', 0)->whereNotNull($column);
		});
		
		return $this;
	}
	
	public function orColumnIsEmpty(string $column): static
	{
		$this->orWhere(fn (self $query) => $query->columnIsEmpty($column));
		
		return $this;
	}
	
	public function orColumnIsNotEmpty(string $column): static
	{
		$this->orWhere(fn (self $query) => $query->columnIsNotEmpty($column));
		
		return $this;
	}
}
