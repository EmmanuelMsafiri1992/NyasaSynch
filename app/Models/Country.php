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
use App\Models\Traits\CountryTrait;
use App\Observers\CountryObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Models\Crud;
use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Models\SpatieTranslatable\HasTranslations;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([CountryObserver::class])]
#[ScopedBy([ActiveScope::class, LocalizedScope::class])]
class Country extends BaseModel
{
	use Crud, AppendsTrait, HasTranslations;
	use CountryTrait;
	
	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = 'countries';
	
	/**
	 * The primary key for the model.
	 *
	 * @var string
	 */
	protected $primaryKey = 'code';
	protected $keyType = 'string';
	public $incrementing = false;
	
	/**
	 * @var array<int, string>
	 */
	protected $appends = [
		'icode',
		'flag_url',
		'flag16_url',
		'flag24_url',
		'flag32_url',
		'flag48_url',
		'flag64_url',
		'background_image_url',
	];
	
	/**
	 * @var array<int, string>
	 */
	protected $visible = [
		'code',
		'name',
		'icode',
		'iso3',
		'currency_code',
		'phone',
		'languages',
		'currency',
		'time_zone',
		'date_format',
		'datetime_format',
		'background_image',
		'flag_url',
		'flag16_url',
		'flag24_url',
		'flag32_url',
		'flag48_url',
		'flag64_url',
		'background_image_url',
		'admin_type',
	];
	
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
		'code',
		'name',
		'capital',
		'continent_code',
		'tld',
		'currency_code',
		'phone',
		'languages',
		'time_zone',
		'date_format',
		'datetime_format',
		'background_image',
		'admin_type',
		'active',
	];
	
	/**
	 * @var array<int, string>
	 */
	public array $translatable = ['name'];
	
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
	public function currency(): BelongsTo
	{
		return $this->belongsTo(Currency::class, 'currency_code', 'code');
	}
	
	public function posts(): HasMany
	{
		return $this->hasMany(Post::class, 'country_code')->orderByDesc('created_at');
	}
	
	public function users(): HasMany
	{
		return $this->hasMany(User::class, 'country_code')->orderByDesc('created_at');
	}
	
	/*
	|--------------------------------------------------------------------------
	| SCOPES
	|--------------------------------------------------------------------------
	*/
	public function scopeActive(Builder $query): Builder
	{
		if (request()->segment(1) == admin_uri()) {
			if (str_contains(currentRouteAction(), 'Admin\CountryController')) {
				return $query;
			}
		}
		
		return $query->where('active', 1);
	}
	
	/*
	|--------------------------------------------------------------------------
	| ACCESSORS | MUTATORS
	|--------------------------------------------------------------------------
	*/
	protected function icode(): Attribute
	{
		return Attribute::make(
			get: fn ($value) => strtolower($this->code ?? ($this->attributes['code'] ?? '')),
		);
	}
	
	protected function id(): Attribute
	{
		return Attribute::make(
			get: fn ($value) => $this->code ?? ($this->attributes['code'] ?? $value),
		);
	}
	
	protected function name(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				if (isset($this->attributes['name']) && !isJson($this->attributes['name'])) {
					return $this->attributes['name'];
				}
				
				return $value;
			},
		);
	}
	
	protected function languages(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				$value = explode(',', $value);
				
				return collect($value)
					->map(function ($item) {
						$item = str_replace('-', '_', $item);
						
						return getPrimaryLocaleCode($item);
					})
					->implode(',');
			},
		);
	}
	
	protected function flagUrl(): Attribute
	{
		return Attribute::make(
			get: function () {
				return getCountryFlagUrl($this->code);
			},
		);
	}
	
	protected function flag16Url(): Attribute
	{
		return Attribute::make(
			get: function () {
				return getCountryFlagUrl($this->code, 16);
			},
		);
	}
	
	protected function flag24Url(): Attribute
	{
		return Attribute::make(
			get: function () {
				return getCountryFlagUrl($this->code, 24);
			},
		);
	}
	
	protected function flag32Url(): Attribute
	{
		return Attribute::make(
			get: function () {
				return getCountryFlagUrl($this->code, 32);
			},
		);
	}
	
	protected function flag48Url(): Attribute
	{
		return Attribute::make(
			get: function () {
				return getCountryFlagUrl($this->code, 48);
			},
		);
	}
	
	protected function flag64Url(): Attribute
	{
		return Attribute::make(
			get: function () {
				return getCountryFlagUrl($this->code, 64);
			},
		);
	}
	
	protected function backgroundImageUrl(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				$bgImageUrl = null;
				if (!empty($this->background_image)) {
					$disk = StorageDisk::getDisk();
					if ($disk->exists($this->background_image)) {
						$bgImageUrl = imgUrl($this->background_image, 'bg-header');
					}
				}
				
				return $bgImageUrl;
			},
		);
	}
	
	/*
	|--------------------------------------------------------------------------
	| OTHER PRIVATE METHODS
	|--------------------------------------------------------------------------
	*/
}
