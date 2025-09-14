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

use App\Models\Builders\Classes\Helpers\JsonHelper;
use App\Helpers\DBTool;

/*
 * Builder for translatable models ***
 * The create(), update(), find(), findOrFail(), findMany(), findBySlug(), and findBySlugOrFail() methods
 * for translatable models are implemented in the:
 * 'app/Http/Controllers/Web/Admin/Panel/Library/Traits/Models/SpatieTranslatable/HasTranslations.php' file
 */
class TranslationsBuilder extends GlobalBuilder
{
	public function where($column, $operator = null, $value = null, $boolean = 'and'): static
	{
		if ($column instanceof \Closure) {
			return parent::where($column, $operator, $value, $boolean);
		}
		
		// Is it a translatable model? If so, check if the column is translatable
		// Model or column not translatable
		$model = $this->model ?? null;
		if (!isTranslatableColumn($model, $column)) {
			return parent::where($column, $operator, $value, $boolean);
		}
		
		// Translatable model and column
		if (func_num_args() == 2 && empty($value)) {
			$value = $operator;
		}
		
		$locale = $locale ?? app()->getLocale();
		$masterLocale = config('translatable.fallback_locale') ?? config('app.fallback_locale');
		
		// Escaping Quote
		$value = str_replace(['\''], ['\\\''], $value);
		
		// JSON columns manipulation is only available in:
		// MySQL 5.7 or above & MariaDB 10.2.3 or above
		$jsonMethodsAreAvailable = (
			(!DBTool::isMariaDB() && DBTool::isMySqlMinVersion('5.7'))
			|| (DBTool::isMariaDB() && DBTool::isMySqlMinVersion('10.2.3'))
		);
		if ($jsonMethodsAreAvailable) {
			
			return parent::where(function (self $query) use ($column, $locale, $value, $masterLocale) {
				$jsonColumn = JsonHelper::jsonExtract($column, $locale);
				$jsonColumn = 'LOWER(' . $jsonColumn . ')';
				$jsonColumn = 'BINARY ' . $jsonColumn;
				
				$value = 'LOWER(\'' . $value . '\')';
				$value = 'BINARY ' . $value;
				
				$query->whereRaw($jsonColumn . ' LIKE ' . $value);
				
				if (!empty($masterLocale) && $locale != $masterLocale) {
					$jsonColumn = JsonHelper::jsonExtract($column, $masterLocale);
					$jsonColumn = 'LOWER(' . $jsonColumn . ')';
					$jsonColumn = 'BINARY ' . $jsonColumn;
					
					$query->orWhereRaw($jsonColumn . ' LIKE ' . $value);
				}
			});
			
		} else {
			
			$value = str($value)->start('%')->toString();
			$value = str($value)->finish('%')->toString();
			
			return parent::where($column, 'LIKE', $value, $boolean);
			
		}
	}
	
	public function orWhere($column, $operator = null, $value = null): static
	{
		// Is it a translatable model? If so, check if the column is translatable
		// Model or column not translatable
		$model = $this->model ?? null;
		if (!isTranslatableColumn($model, $column)) {
			return parent::orWhere($column, $operator, $value);
		}
		
		// Translatable model and column
		return parent::orWhere(fn (self $query) => $query->where($column, $operator, $value));
	}
	
	public function orderBy($column, $direction = 'asc', $locale = null): TranslationsBuilder|static
	{
		// Is it a translatable model? If so, check if the column is translatable
		// Model or column not translatable
		$model = $this->model ?? null;
		if (!isTranslatableColumn($model, $column)) {
			return parent::orderBy($column, $direction);
		}
		
		// Translatable model and column
		$locale = $locale ?? app()->getLocale();
		$masterLocale = config('translatable.fallback_locale') ?? config('app.fallback_locale');
		
		$jsonMethodsAreAvailable = (
			(!DBTool::isMariaDB() && DBTool::isMySqlMinVersion('5.7'))
			|| (DBTool::isMariaDB() && DBTool::isMySqlMinVersion('10.2.3'))
		);
		if ($jsonMethodsAreAvailable) {
			
			$jsonColumn = JsonHelper::jsonExtract($column, $locale);
			$this->orderByRaw($jsonColumn . ' ' . $direction);
			
			if (!empty($masterLocale) && $locale != $masterLocale) {
				$jsonColumn = JsonHelper::jsonExtract($column, $masterLocale);
				$this->orderByRaw($jsonColumn . ' ' . $direction);
			}
			
		} else {
			
			/*
			 * Remove the first part of the column up to and including the first "$locale":"
			 * IMPORTANT: To prevent MySQL limitation use '"en":' instead of '"en":"' that provide wrong result.
			 * DEBUG: SELECT LOCATE('"en":', name) as nPos, SUBSTR(name, LOCATE('"en":', name)+6) as cName FROM lc_categories WHERE parent_id IS NULL;
			 */
			$subStr = '"' . $locale . '":';
			$subStrPos = 'LOCATE(\'' . $subStr . '\', ' . $column . ')';
			$jsonColumn = 'SUBSTR(' . $column . ', ' . $subStrPos . '+' . (strlen($subStr) + 1) . ')';
			$jsonColumn = 'IF(' . $subStrPos . ' > 0, ' . $jsonColumn . ', NULL)';
			// With COALESCE(), returns the first non-NULL value in a specified list of arguments (here 'zz')
			$jsonColumn = 'COALESCE(' . $jsonColumn . ', \'zz\')';
			$this->orderByRaw($jsonColumn . ' ' . $direction);
			
			if (!empty($masterLocale) && $locale != $masterLocale) {
				$subStr = '"' . $masterLocale . '":';
				$subStrPos = 'LOCATE(\'' . $subStr . '\', ' . $column . ')';
				$jsonColumn = 'SUBSTR(' . $column . ', ' . $subStrPos . '+' . (strlen($subStr) + 1) . ')';
				$jsonColumn = 'IF(' . $subStrPos . ' > 0, ' . $jsonColumn . ', ' . $column . ')';
				$this->orderByRaw($jsonColumn . ' ' . $direction);
			}
			
		}
		
		return $this;
	}
}
