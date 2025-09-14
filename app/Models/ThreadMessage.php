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
use App\Models\Thread\MessageTrait;
use App\Models\Traits\Common\AppendsTrait;
use App\Observers\ThreadMessageObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Models\Crud;

#[ObservedBy([ThreadMessageObserver::class])]
class ThreadMessage extends BaseModel
{
	use SoftDeletes, Crud, AppendsTrait, Notifiable, MessageTrait, HasFactory;
	
	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = 'threads_messages';
	
	/**
	 * @var array<int, string>
	 */
	protected $appends = ['created_at_formatted', 'p_recipient'];
	
	/**
	 * The attributes that aren't mass assignable.
	 *
	 * @var array<int, string>
	 */
	protected $guarded = ['id'];
	
	/**
	 * The relationships that should be touched on save.
	 *
	 * @var array<int, string>
	 */
	protected $touches = ['thread'];
	
	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array<int, string>
	 */
	protected $fillable = [
		'thread_id',
		'user_id',
		'body',
		'filename',
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
	
	/**
	 * Participants relationship.
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 *
	 * @codeCoverageIgnore
	 */
	public function participants(): HasMany
	{
		return $this->hasMany(ThreadParticipant::class, 'thread_id', 'thread_id');
	}
	
	/*
	|--------------------------------------------------------------------------
	| SCOPES
	|--------------------------------------------------------------------------
	*/
	public function scopeNotDeletedByUser(Builder $query, $userId): Builder
	{
		return $query->where(function (Builder $q) use ($userId) {
			$q->where('deleted_by', '!=', $userId)->orWhereNull('deleted_by');
		});
	}
	
	/**
	 * Returns unread messages given the userId.
	 *
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 * @param int $userId
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	public function scopeUnreadForUser(Builder $query, int $userId): Builder
	{
		$rawTable = $this->getConnection()->getTablePrefix() . $this->getTable();
		$rawCreatedAt = $this->getConnection()->raw($rawTable . '.created_at');
		
		return $query->has('thread')
			->where('user_id', '!=', $userId)
			->whereHas('participants', function (Builder $query) use ($userId, $rawCreatedAt) {
				$query->where('user_id', $userId)
					->where(function (Builder $q) use ($rawCreatedAt) {
						$q->where('last_read', '<', $rawCreatedAt)->orWhereNull('last_read');
					});
			});
	}
	
	/*
	|--------------------------------------------------------------------------
	| ACCESSORS | MUTATORS
	|--------------------------------------------------------------------------
	*/
	protected function createdAt(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				$value = new Carbon($value);
				$value->timezone(Date::getAppTimeZone());
				
				return $value;
			},
		);
	}
	
	protected function createdAtFormatted(): Attribute
	{
		return Attribute::make(
			get: function () {
				$value = $this->created_at ?? ($this->attributes['created_at'] ?? null);
				
				if (!$value instanceof Carbon) {
					$value = new Carbon($value);
					$value->timezone(Date::getAppTimeZone());
				}
				
				return Date::format($value, 'datetime');
			},
		);
	}
	
	protected function pRecipient(): Attribute
	{
		return Attribute::make(
			get: function () {
				return $this->participants()->where('user_id', '!=', $this->user_id)->first();
			},
		);
	}
	
	/*
	|--------------------------------------------------------------------------
	| OTHER PRIVATE METHODS
	|--------------------------------------------------------------------------
	*/
}
