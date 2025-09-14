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

use App\Models\Traits\Common\AppendsTrait;
use App\Models\Traits\PermissionTrait;
use App\Observers\PermissionObserver;
use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Models\Crud;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Spatie\Permission\Models\Permission as OriginalPermission;

#[ObservedBy([PermissionObserver::class])]
class Permission extends OriginalPermission
{
	use Crud, AppendsTrait;
	use PermissionTrait;
	
	/**
	 * @var array<int, string>
	 */
	protected $fillable = ['name', 'guard_name', 'updated_at', 'created_at'];
	
	/*
	|--------------------------------------------------------------------------
	| FUNCTIONS
	|--------------------------------------------------------------------------
	*/
	/**
	 * Default Super Admin users permissions
	 *
	 * @return array<int, string>
	 */
	public static function getSuperAdminPermissions(): array
	{
		return [
			'permission-list',
			'permission-create',
			'permission-update',
			'permission-delete',
			'role-list',
			'role-create',
			'role-update',
			'role-delete',
		];
	}
	
	/**
	 * Default Staff users permissions
	 *
	 * @return array<int, string>
	 */
	public static function getStaffPermissions(): array
	{
		return [
			'dashboard-access',
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
	
	/*
	|--------------------------------------------------------------------------
	| OTHER PRIVATE METHODS
	|--------------------------------------------------------------------------
	*/
}
