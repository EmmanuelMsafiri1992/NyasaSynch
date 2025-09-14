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

namespace App\Http\Resources;

use App\Helpers\Files\Storage\StorageDisk;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;

class EntityCollection extends ResourceCollection
{
	public string $entityResource;
	
	/**
	 * EntityCollection constructor.
	 *
	 * @param $controllerName
	 * @param $resource
	 */
	public function __construct($controllerName, $resource)
	{
		parent::__construct($resource);
		
		$this->entityResource = str($controllerName)->replaceLast('Controller', 'Resource')->toString();
		if (!str_starts_with($this->entityResource, '\\')) {
			$this->entityResource = '\\' . __NAMESPACE__ . '\\' . $this->entityResource;
		}
	}
	
	/**
	 * Transform the resource into an array.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return array
	 */
	public function toArray(Request $request): array
	{
		$collection = $this->collection->transform(function ($entity) {
			return new $this->entityResource($entity);
		});
		
		// ResumeCollection
		if (str_ends_with($this->entityResource, 'ResumeResource')) {
			$collection = $this->applyResumeFilters($collection);
		}
		
		return [
			'data' => $collection,
		];
	}
	
	// PRIVATE
	
	/**
	 * @param \Illuminate\Support\Collection $collection
	 * @return \Illuminate\Support\Collection
	 */
	private function applyResumeFilters(Collection $collection): Collection
	{
		// If the résumés list is for selection to apply to a job, make sure that the attached file exists
		$isForApplyingJob = (request()->filled('forApplyingJob') && request()->integer('forApplyingJob') == 1);
		if ($isForApplyingJob) {
			$pDisk = StorageDisk::getDisk('private');
			$limit = config('larapen.core.selectResumeInto', 5);
			
			$collection = $collection->reject(function ($entity, $key) use ($pDisk) {
				$fileExists = (!empty($entity['filename']) && $pDisk->exists($entity['filename']));
				
				return !$fileExists;
			})->take($limit);
		}
		
		return $collection;
	}
}
