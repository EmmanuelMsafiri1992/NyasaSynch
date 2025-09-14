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

use App\Helpers\Date;
use App\Models\Traits\Common\AppendsTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Models\Crud;

class ThreadParticipant extends BaseModel
{
	use SoftDeletes, Crud, AppendsTrait, Notifiable, HasFactory;
	
	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = 'threads_participants';
	
	/**
	 * @var array<int, string>
	 */
	protected $appends = ['created_at_formatted'];
	
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
		'thread_id',
		'user_id',
		'last_read',
		'is_important',
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
			'created_at' => 'datetime',
			'updated_at' => 'datetime',
			'deleted_at' => 'datetime',
		];
	}
	
	/*
	|--------------------------------------------------------------------------
	| RELATIONS
	|--------------------------------------------------------------------------
	*/
	/**
	 * Thread relationship.
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 *
	 * @codeCoverageIgnore
	 */
	public function thread(): BelongsTo
	{
		return $this->belongsTo(Thread::class, 'thread_id', 'id');
	}
	
	/**
	 * User relationship.
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 *
	 * @codeCoverageIgnore
	 */
	public function user(): BelongsTo
	{
		return $this->belongsTo(User::class, 'user_id');
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
	protected function createdAtFormatted(): Attribute
	{
		return Attribute::make(
			get: function () {
				$value = $this->created_at ?? ($this->attributes['created_at'] ?? null);
				
				if (!$value instanceof Carbon) {
					$value = new Carbon($value);
					$value->timezone(Date::getAppTimeZone());
				}
				
				return Date::customFromNow($value);
			},
		);
	}
	
	/*
	|--------------------------------------------------------------------------
	| OTHER PRIVATE METHODS
	|--------------------------------------------------------------------------
	*/
}
