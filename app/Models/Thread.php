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
use App\Models\Thread\ThreadTrait;
use App\Models\Traits\Common\AppendsTrait;
use App\Observers\ThreadObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Models\Crud;

#[ObservedBy([ThreadObserver::class])]
class Thread extends BaseModel
{
	use SoftDeletes, Crud, AppendsTrait, Notifiable, ThreadTrait, HasFactory;
	
	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = 'threads';
	
	/**
	 * @var array<int, string>
	 */
	protected $appends = ['created_at_formatted', 'p_is_unread', 'p_creator', 'p_is_important'];
	
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
		'post_id',
		'subject',
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
	public function post(): BelongsTo
	{
		return $this->belongsTo(Post::class, 'post_id');
	}
	
	/**
	 * Messages relationship.
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 *
	 * @codeCoverageIgnore
	 */
	public function messages(): HasMany
	{
		return $this->hasMany(ThreadMessage::class, 'thread_id', 'id')->orderByDesc('id');
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
		return $this->hasMany(ThreadParticipant::class, 'thread_id', 'id');
	}
	
	/**
	 * User's relationship.
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 *
	 * @codeCoverageIgnore
	 */
	public function users(): BelongsToMany
	{
		return $this->belongsToMany(User::class, (new ThreadParticipant)->getTable(), 'thread_id', 'user_id');
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
	 * Returns threads that the user is associated with.
	 *
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 * @param $userId
	 * @return mixed
	 */
	public function scopeForUser(Builder $query, $userId)
	{
		$participantsTable = (new ThreadParticipant)->getTable();
		$threadsTable = $this->getTable();
		
		return $query->notDeletedByUser($userId)
			->join($participantsTable, $this->getQualifiedKeyName(), '=', $participantsTable . '.thread_id')
			->where($participantsTable . '.user_id', $userId)
			->whereNull($participantsTable . '.deleted_at')
			->select($threadsTable . '.*', $participantsTable . '.last_read', $participantsTable . '.is_important');
	}
	
	/**
	 * Returns threads with new messages that the user is associated with.
	 *
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 * @param int $userId
	 * @return mixed
	 */
	public function scopeForUserWithNewMessages(Builder $query, int $userId)
	{
		$participantsTable = (new ThreadParticipant)->getTable();
		$threadsTable = $this->getTable();
		
		return $query->notDeletedByUser($userId)
			->join($participantsTable, $this->getQualifiedKeyName(), '=', $participantsTable . '.thread_id')
			->where($participantsTable . '.user_id', $userId)
			->whereNull($participantsTable . '.deleted_at')
			->where(function (Builder $query) use ($participantsTable, $threadsTable) {
				$query->where(
					$threadsTable . '.updated_at',
					'>',
					$this->getConnection()->raw($this->getConnection()->getTablePrefix() . $participantsTable . '.last_read')
				)->orWhereNull($participantsTable . '.last_read');
			})
			->select($threadsTable . '.*', $participantsTable . '.last_read', $participantsTable . '.is_important');
	}
	
	public function scopeWithoutTimestamps()
	{
		$this->timestamps = false;
		
		return $this;
	}
	
	/**
	 * Returns threads between given user ids.
	 *
	 * @param \Illuminate\Database\Eloquent\Builder $query
	 * @param array $participants
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	public function scopeBetween(Builder $query, array $participants): Builder
	{
		return $query->whereHas('participants', function (Builder $q) use ($participants) {
			$q->whereIn('user_id', $participants)
				->select($this->getConnection()->raw('DISTINCT(thread_id)'))
				->groupBy('thread_id')
				->havingRaw('COUNT(thread_id)=' . count($participants));
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
	
	protected function pIsUnread(): Attribute
	{
		return Attribute::make(
			get: function () {
				if (!isset($this->updated_at) || !($this->updated_at instanceof Carbon)) {
					return false;
				}
				
				try {
					if ($this->last_read === null || $this->updated_at->gt($this->last_read)) {
						return true;
					}
				} catch (\Throwable $e) {
				}
				
				return false;
			},
		);
	}
	
	protected function pCreator(): Attribute
	{
		return Attribute::make(
			get: function () {
				$firstMessage = $this->messages()->withTrashed()->oldest()->first();
				
				return !empty($firstMessage) ? $firstMessage->user->toArray() : [];
			},
		);
	}
	
	
	protected function pIsImportant(): Attribute
	{
		return Attribute::make(
			get: function () {
				try {
					return ($this->is_important == 1);
				} catch (\Throwable $e) {
				}
				
				return false;
			},
		);
	}
	
	/*
	|--------------------------------------------------------------------------
	| OTHER PRIVATE METHODS
	|--------------------------------------------------------------------------
	*/
}
