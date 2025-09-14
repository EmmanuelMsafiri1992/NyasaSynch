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
use App\Models\Scopes\ActiveScope;
use App\Models\Scopes\LocalizedScope;
use App\Models\Traits\Common\AppendsTrait;
use App\Observers\ResumeObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Models\Crud;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([ResumeObserver::class])]
#[ScopedBy([ActiveScope::class, LocalizedScope::class])]
class Resume extends BaseModel
{
	use Crud, AppendsTrait, HasFactory;
	
	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = 'resumes';
	
	/**
	 * @var array<int, string>
	 */
	protected $appends = ['country_flag_url'];
	
	/**
	 * Indicates if the model should be timestamped.
	 *
	 * @var boolean
	 */
	public $timestamps = true;
	
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
	protected $fillable = ['country_code', 'user_id', 'name', 'filename', 'active'];
	
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
			'created_at' => 'datetime',
			'updated_at' => 'datetime',
		];
	}
	
	/*
	|--------------------------------------------------------------------------
	| RELATIONS
	|--------------------------------------------------------------------------
	*/
	public function posts(): HasMany
	{
		return $this->hasMany(Post::class);
	}
	
	public function user(): BelongsToMany
	{
		return $this->belongsToMany(User::class, 'user_id', 'id');
	}
	
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
			get: function ($value, $attributes) {
				if (empty($value)) {
					if (isset($attributes['name'])) {
						$value = $attributes['name'];
					}
				}
				
				if (!empty($value)) {
					$value = last(explode('/', $value));
				}
				
				if (empty($value)) {
					if (!empty($this->filename) && is_string($this->filename)) {
						$value = last(explode('/', $this->filename));
					}
				}
				
				return $value;
			},
		);
	}
	
	protected function filename(): Attribute
	{
		return Attribute::make(
			get: function ($value, $attributes) {
				if (empty($value)) {
					if (isset($attributes['filename'])) {
						$value = $attributes['filename'];
					}
				}
				
				// OLD PATH
				$value = $this->getFilenameFromOldPath($value);
				
				// NEW PATH
				$pDisk = StorageDisk::getDisk('private');
				if (empty($value) || !$pDisk->exists($value)) {
					return null;
				}
				
				return $value;
			},
		);
	}
	
	protected function countryFlagUrl(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				return getCountryFlagUrl($this->country_code);
			},
		);
	}
	
	/*
	|--------------------------------------------------------------------------
	| OTHER PRIVATE METHODS
	|--------------------------------------------------------------------------
	*/
	private function getFilenameFromOldPath($value): ?string
	{
		// Fix path
		$oldBase = 'resumes/'; // ../path/to/resumes/
		$newBase = 'resumes/';
		if (str_contains($value, $oldBase)) {
			$value = $newBase . last(explode($oldBase, $value));
		}
		
		return $value;
	}
}
