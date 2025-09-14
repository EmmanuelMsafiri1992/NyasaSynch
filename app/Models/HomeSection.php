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

namespace App\Models;

use App\Helpers\Files\Storage\StorageDisk;
use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Models\Crud;
use App\Models\Scopes\ActiveScope;
use App\Models\Traits\Common\AppendsTrait;
use App\Models\Traits\HomeSectionTrait;
use App\Observers\HomeSectionObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[ObservedBy([HomeSectionObserver::class])]
#[ScopedBy([ActiveScope::class])]
class HomeSection extends BaseModel
{
	use Crud, AppendsTrait, HasFactory;
	use HomeSectionTrait;
	
	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = 'home_sections';
	
	/**
	 * @var array<int, string>
	 */
	protected array $fakeColumns = ['value'];
	
	/**
	 * The primary key for the model.
	 *
	 * @var string
	 */
	protected $primaryKey = 'id';
	
	/**
	 * Indicates if the model should be timestamped.
	 *
	 * @var boolean
	 */
	public $timestamps = false;
	
	/**
	 * The attributes that aren't mass assignable.
	 *
	 * @var array<int, string>
	 */
	protected $guarded = ['id'];
	
	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array<int, string>
	 */
	protected $fillable = [
		'method',
		'name',
		'value',
		'view',
		'field',
		'parent_id',
		'lft',
		'rgt',
		'depth',
		'active',
	];
	
	/*
	|--------------------------------------------------------------------------
	| FUNCTIONS
	|--------------------------------------------------------------------------
	*/
	/**
	 * Get the attributes that should be cast.
	 *
	 * @return array<string, string>
	 */
	protected function casts(): array
	{
		return [
			'value' => 'array',
		];
	}
	
	/*
	|--------------------------------------------------------------------------
	| RELATIONS
	|--------------------------------------------------------------------------
	*/
	
	/*
	|--------------------------------------------------------------------------
	| SCOPES
	|--------------------------------------------------------------------------
	*/
	
	/*
	|--------------------------------------------------------------------------
	| ACCESSORS | MUTATORS
	|--------------------------------------------------------------------------
	*/
	protected function name(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				if (isset($this->method)) {
					$transKey = 'settings.home_' . $this->method;
					
					if (trans()->has($transKey)) {
						$value = trans($transKey);
					}
				}
				
				return $value;
			},
		);
	}
	
	protected function field(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				$diskName = StorageDisk::getDiskName();
				
				// Get 'field' field value
				$value = jsonToArray($value);
				
				$breadcrumb = trans('admin.Admin panel') . ' &rarr; '
					. mb_ucwords(trans('admin.settings')) . ' &rarr; '
					. mb_ucwords(trans('admin.homepage')) . ' &rarr; ';
				
				$name = $this->name ?? 'Options';
				$description = mb_ucfirst(trans('admin.homepage')) . ': ' . $name;
				$title = !empty($description) ? $description : $name;
				
				$formTitle = [
					[
						'name'  => 'group_title',
						'type'  => 'custom_html',
						'value' => '<h2 class="setting-group-name">' . $title . '</h2>',
					],
					[
						'name'  => 'group_breadcrumb',
						'type'  => 'custom_html',
						'value' => '<p class="setting-group-breadcrumb">' . $breadcrumb . $name . '</p>',
					],
				];
				
				// Handle 'field' field value
				// Get the right Setting
				$settingClass = $this->getSettingClass();
				if (class_exists($settingClass)) {
					if (method_exists($settingClass, 'getFields')) {
						$value = $settingClass::getFields($diskName);
					}
				}
				
				return array_merge($formTitle, $value);
			},
		);
	}
	
	protected function value(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				// Get 'value' field value
				$value = jsonToArray($value);
				
				// Handle 'value' field value
				// Get the right Setting
				$settingClass = $this->getSettingClass();
				if (class_exists($settingClass)) {
					if (method_exists($settingClass, 'getValues')) {
						$value = $settingClass::getValues($value);
					}
				}
				
				return $value;
			},
			set: function ($value) {
				$value = jsonToArray($value);
				
				// Handle 'value' field value
				// Get the right Setting
				$settingClass = $this->getSettingClass();
				if (class_exists($settingClass)) {
					if (method_exists($settingClass, 'setValues')) {
						$value = $settingClass::setValues($value, $this);
					}
				}
				
				// Make sure that setting array contains only string, numeric or null elements
				$value = settingArrayElements($value);
				
				return !empty($value) ? json_encode($value) : null;
			},
		);
	}
	
	/*
	|--------------------------------------------------------------------------
	| OTHER PRIVATE METHODS
	|--------------------------------------------------------------------------
	*/
	/**
	 * Get the right Setting class
	 *
	 * @return string
	 */
	private function getSettingClass(): string
	{
		$classKey = $this->method ?? '';
		
		// Get class name
		$className = str($classKey)->camel()->ucfirst();
		
		// Get class full qualified name (i.e. with namespace)
		$namespace = '\App\Models\HomeSection\\';
		
		return $className->prepend($namespace)->toString();
	}
}
