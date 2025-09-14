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

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Web\Admin\Traits\SettingsTrait;
use App\Models\HomeSection;
use App\Http\Controllers\Web\Admin\Panel\PanelController;
use App\Http\Requests\Admin\Request as StoreRequest;
use App\Http\Requests\Admin\Request as UpdateRequest;

class HomeSectionController extends PanelController
{
	use SettingsTrait;
	
	public function setup()
	{
		/*
		|--------------------------------------------------------------------------
		| BASIC CRUD INFORMATION
		|--------------------------------------------------------------------------
		*/
		$this->xPanel->setModel(HomeSection::class);
		$this->xPanel->setRoute(admin_uri('homepage'));
		$this->xPanel->setEntityNameStrings(trans('admin.homepage section'), trans('admin.homepage sections'));
		$this->xPanel->denyAccess(['create', 'delete']);
		$this->xPanel->allowAccess(['reorder']);
		$this->xPanel->enableReorder('name', 1);
		if (!request()->input('order')) {
			$this->xPanel->orderBy('lft');
		}
		
		$this->xPanel->addButtonFromModelFunction('top', 'reset_homepage_reorder', 'resetHomepageReOrderButton', 'end');
		$this->xPanel->addButtonFromModelFunction('top', 'reset_homepage_settings', 'resetHomepageSettingsButton', 'end');
		$this->xPanel->removeButton('update');
		$this->xPanel->addButtonFromModelFunction('line', 'configure', 'configureButton', 'beginning');
		
		// Filters
		// -----------------------
		$this->xPanel->disableSearchBar();
		// -----------------------
		$this->xPanel->addFilter(
			[
				'name'  => 'name',
				'type'  => 'text',
				'label' => mb_ucfirst(trans('admin.Name')),
			],
			false,
			fn ($value) => $this->xPanel->addClause('where', 'name', 'LIKE', "%$value%")
		);
		// -----------------------
		$this->xPanel->addFilter(
			[
				'name'  => 'status',
				'type'  => 'dropdown',
				'label' => trans('admin.Status'),
			],
			[
				1 => trans('admin.Activated'),
				2 => trans('admin.Unactivated'),
			],
			function ($value) {
				if ($value == 1) {
					$this->xPanel->addClause('where', 'active', '=', 1);
				}
				if ($value == 2) {
					$this->xPanel->addClause('where', fn ($query) => $query->columnIsEmpty('active'));
				}
			}
		);
		
		/*
		|--------------------------------------------------------------------------
		| COLUMNS AND FIELDS
		|--------------------------------------------------------------------------
		*/
		// COLUMNS
		$this->xPanel->addColumn([
			'name'          => 'name',
			'label'         => trans('admin.Section'),
			'type'          => 'model_function',
			'function_name' => 'getNameHtml',
		]);
		$this->xPanel->addColumn([
			'name'          => 'active',
			'label'         => trans('admin.Active'),
			'type'          => 'model_function',
			'function_name' => 'getActiveHtml',
		]);
		
		// FIELDS
		// ...
	}
	
	public function store(StoreRequest $request)
	{
		return parent::storeCrud($request);
	}
	
	public function update(UpdateRequest $request)
	{
		$section = HomeSection::find(request()->segment(3));
		if (!empty($section)) {
			// Get the right Setting class
			$classKey = $section->method ?? '';
			
			// Get class name
			$className = str($classKey)->camel()->ucfirst();
			
			// Get class full qualified name (i.e. with namespace)
			$namespace = '\App\Models\HomeSection\\';
			$class = $className->prepend($namespace)->toString();
			
			// If the class doesn't exist in the core app, try to get it from add-ons
			if (!class_exists($class)) {
				$namespace = plugin_namespace($classKey) . '\app\Models\HomeSection\\';
				$class = $className->prepend($namespace)->toString();
			}
			
			if (class_exists($class)) {
				if (method_exists($class, 'passedValidation')) {
					$request = $class::passedValidation($request);
				}
			}
		}
		
		return $this->updateTrait($request);
	}
	
	/**
	 * Find a home section's real URL
	 * admin_url('homepage/find/{key}')
	 *
	 * @param $method
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function find($method): \Illuminate\Http\RedirectResponse
	{
		$homeSection = HomeSection::where('method', $method)->first();
		if (empty($homeSection)) {
			$message = trans('admin.home_section_not_found', ['homeSection' => $method]);
			notification($message, 'error');
			
			return redirect()->back();
		}
		
		$url = admin_url('homepage/' . $homeSection->id . '/edit');
		
		return redirect()->to($url);
	}
	
	/**
	 * Homepage Sections Actions (Reset Order & Settings)
	 * admin_url('homepage/reset/all/{action}')
	 *
	 * @param $action
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function resetAll($action): \Illuminate\Http\RedirectResponse
	{
		// Reset the homepage sections reorder
		if ($action == 'reorder') {
			HomeSection::where('method', 'getSearchForm')->update(['lft' => 0, 'rgt' => 1, 'active' => 1]);
			HomeSection::where('method', 'getPremiumListings')->update(['lft' => 2, 'rgt' => 3, 'active' => 1]);
			HomeSection::where('method', 'getLatestListings')->update(['lft' => 4, 'rgt' => 5, 'active' => 1]);
			HomeSection::where('method', 'getCategories')->update(['lft' => 6, 'rgt' => 7, 'active' => 1]);
			HomeSection::where('method', 'getLocations')->update(['lft' => 8, 'rgt' => 9, 'active' => 1]);
			HomeSection::where('method', 'getCompanies')->update(['lft' => 10, 'rgt' => 11, 'active' => 1]);
			HomeSection::where('method', 'getStats')->update(['lft' => 12, 'rgt' => 13, 'active' => 1]);
			HomeSection::where('method', 'getTextArea')->update(['lft' => 14, 'rgt' => 15, 'active' => 0]);
			HomeSection::where('method', 'getTopAdvertising')->update(['lft' => 16, 'rgt' => 17, 'active' => 0]);
			HomeSection::where('method', 'getBottomAdvertising')->update(['lft' => 18, 'rgt' => 19, 'active' => 0]);
			
			$message = trans('admin.The homepage sections reorganization were been reset successfully');
			notification($message, 'success');
		}
		
		// Reset all the homepage settings
		if ($action == 'options') {
			HomeSection::where('method', 'getSearchForm')->update(['value' => null, 'active' => 1]);
			HomeSection::where('method', 'getPremiumListings')->update(['value' => null, 'active' => 1]);
			HomeSection::where('method', 'getLatestListings')->update(['value' => null, 'active' => 1]);
			HomeSection::where('method', 'getCategories')->update(['value' => null, 'active' => 1]);
			HomeSection::where('method', 'getLocations')->update(['value' => null, 'active' => 1]);
			HomeSection::where('method', 'getCompanies')->update(['value' => null, 'active' => 1]);
			HomeSection::where('method', 'getStats')->update(['value' => null, 'active' => 1]);
			HomeSection::where('method', 'getTextArea')->update(['value' => null, 'active' => 0]);
			HomeSection::where('method', 'getTopAdvertising')->update(['value' => null, 'active' => 0]);
			HomeSection::where('method', 'getBottomAdvertising')->update(['value' => null, 'active' => 0]);
			
			// Delete files which has 'header-' as prefix
			try {
				
				// List all files in the "app/logo/" path,
				// Filter the ones that match the "*header-*.*" pattern,
				// And delete them.
				$allFiles = $this->disk->files('app/logo/');
				$matchingFiles = preg_grep('/.+\/header-.+\./', $allFiles);
				$this->disk->delete($matchingFiles);
				
			} catch (\Throwable $e) {
			}
			
			$message = trans('admin.All the homepage settings were been reset successfully');
			notification($message, 'success');
		}
		
		if (in_array($action, ['reorder', 'options'])) {
			cache()->flush();
		} else {
			$message = trans('admin.No action has been performed');
			notification($message, 'warning');
		}
		
		return redirect()->back();
	}
}
