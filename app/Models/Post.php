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
use App\Helpers\Files\Storage\StorageDisk;
use App\Helpers\Num;
use App\Helpers\RemoveFromString;
use App\Models\Post\SimilarByCategory;
use App\Models\Post\SimilarByLocation;
use App\Models\Scopes\LocalizedScope;
use App\Models\Scopes\StrictActiveScope;
use App\Models\Scopes\ValidPeriodScope;
use App\Models\Scopes\VerifiedScope;
use App\Models\Scopes\ReviewedScope;
use App\Models\Traits\Common\AppendsTrait;
use App\Models\Traits\Common\HasCountryCodeColumn;
use App\Models\Traits\PostTrait;
use App\Observers\PostObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Web\Admin\Panel\Library\Traits\Models\Crud;
use Spatie\Feed\Feedable;

#[ObservedBy([PostObserver::class])]
#[ScopedBy([VerifiedScope::class, ReviewedScope::class, LocalizedScope::class])]
class Post extends BaseModel implements Feedable
{
	use Crud, AppendsTrait, HasCountryCodeColumn, Notifiable, HasFactory;
	use PostTrait;
	use SimilarByCategory, SimilarByLocation;
	
	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = 'posts';
	
	/**
	 * The primary key for the model.
	 *
	 * @var string
	 */
	protected $primaryKey = 'id';
	
	/**
	 * @var array<int, string>
	 */
	protected $appends = [
		'reference',
		'slug',
		'url',
		'excerpt',
		'phone_intl',
		'created_at_formatted',
		'logo_url',
		'logo_url_small',
		'logo_url_medium',
		'logo_url_large',
		'user_photo_url',
		'country_flag_url',
		'salary_formatted',
		'visits_formatted',
		'distance_info',
	];
	
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
	protected $fillable = [
		'country_code',
		'user_id',
		'payment_id',
		'company_id',
		'company_name',
		'logo',
		'company_description',
		'category_id',
		'post_type_id',
		'title',
		'description',
		'tags',
		'salary_min',
		'salary_max',
		'salary_type_id',
		'currency_code',
		'negotiable',
		'start_date',
		'application_url',
		'contact_name',
		'auth_field',
		'email',
		'phone',
		'phone_national',
		'phone_country',
		'phone_hidden',
		'city_id',
		'lat',
		'lon',
		'address',
		'create_from_ip',
		'latest_update_ip',
		'visits',
		'tmp_token',
		'email_token',
		'phone_token',
		'email_verified_at',
		'phone_verified_at',
		'accept_terms',
		'accept_marketing_offers',
		'reviewed_at',
		'featured',
		'archived',
		'archived_at',
		'archived_manually_at',
		'deletion_mail_sent_at',
		'partner',
		'created_at',
		'updated_at',
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
			'email_verified_at' => 'datetime',
			'phone_verified_at' => 'datetime',
			'created_at'        => 'datetime',
			'updated_at'        => 'datetime',
			'deleted_at'        => 'datetime',
			'reviewed_at'       => 'datetime',
			'archived_at'       => 'datetime',
		];
	}
	
	public function routeNotificationForMail()
	{
		return $this->email;
	}
	
	public function routeNotificationForVonage()
	{
		$phone = phoneE164($this->phone, $this->phone_country);
		
		return setPhoneSign($phone, 'vonage');
	}
	
	public function routeNotificationForTwilio()
	{
		$phone = phoneE164($this->phone, $this->phone_country);
		
		return setPhoneSign($phone, 'twilio');
	}
	
	/*
	|--------------------------------------------------------------------------
	| QUERIES
	|--------------------------------------------------------------------------
	*/
	
	/*
	|--------------------------------------------------------------------------
	| RELATIONS
	|--------------------------------------------------------------------------
	*/
	public function postType(): BelongsTo
	{
		return $this->belongsTo(PostType::class, 'post_type_id');
	}
	
	public function category(): BelongsTo
	{
		return $this->belongsTo(Category::class, 'category_id');
	}
	
	public function city(): BelongsTo
	{
		return $this->belongsTo(City::class, 'city_id');
	}
	
	public function currency(): BelongsTo
	{
		return $this->belongsTo(Currency::class, 'currency_code', 'code');
	}
	
	/*
	 * The first valid payment (Covers the validity period).
	 * Its activation needs to be checked programmably (if needed).
	 * NOTE: By sorting the ID by ASC, allows the system to use the first valid payment as the current one.
	 */
	public function possiblePayment(): MorphOne
	{
		return $this->morphOne(Payment::class, 'payable')->withoutGlobalScope(StrictActiveScope::class)->orderBy('id');
	}
	
	/*
	 * The first valid & active payment (Covers the validity period & is active)
	 * NOTE: By sorting the ID by ASC, allows the system to use the first valid payment as the current one.
	 */
	public function payment(): MorphOne
	{
		return $this->morphOne(Payment::class, 'payable')->orderBy('id');
	}
	
	/*
	 * The first valid & active payment that is manually created
	 * NOTE: Used in the ListingsPurge command in cron job
	 */
	public function paymentNotManuallyCreated(): MorphOne
	{
		return $this->morphOne(Payment::class, 'payable')->notManuallyCreated()->orderBy('id');
	}
	
	/*
	 * The ending later valid (or on hold) active payment (Covers the validity period & is active)
	 * This is useful to calculate the starting period to allow payable to have multiple valid & active payments
	 */
	public function paymentEndingLater(): MorphOne
	{
		return $this->morphOne(Payment::class, 'payable')
			->withoutGlobalScope(ValidPeriodScope::class)
			->where(function ($q) {
				$q->where(fn ($q) => $q->valid())->orWhere(fn ($q) => $q->onHold());
			})
			->orderByDesc('period_end');
	}
	
	/*
	 * Get all the listing payments
	 */
	public function payments(): MorphMany
	{
		return $this->morphMany(Payment::class, 'payable');
	}
	
	public function pictures(): HasMany
	{
		return $this->hasMany(Picture::class, 'post_id')->orderBy('position')->orderByDesc('id');
	}
	
	public function savedByLoggedUser(): HasMany
	{
		$guard = isFromApi() ? 'sanctum' : null;
		$userId = auth($guard)->user()?->getAuthIdentifier() ?? '-1';
		
		return $this->hasMany(SavedPost::class, 'post_id')->where('user_id', $userId);
	}
	
	public function user(): BelongsTo
	{
		return $this->belongsTo(User::class, 'user_id');
	}
	
	public function company(): BelongsTo
	{
		return $this->belongsTo(Company::class, 'company_id');
	}
	
	public function salaryType(): BelongsTo
	{
		return $this->belongsTo(SalaryType::class, 'salary_type_id');
	}
	
	public function subscription(): BelongsTo
	{
		return $this->belongsTo(Payment::class, 'payment_id');
	}
	
	/*
	|--------------------------------------------------------------------------
	| SCOPES
	|--------------------------------------------------------------------------
	*/
	public function scopeVerified(Builder $builder): Builder
	{
		$builder->where(function (Builder $query) {
			$query->whereNotNull('email_verified_at')->whereNotNull('phone_verified_at');
		});
		
		if (config('settings.listing_form.listings_review_activation')) {
			$builder->whereNotNull('reviewed_at');
		}
		
		return $builder;
	}
	
	public function scopeUnverified(Builder $builder): Builder
	{
		$builder->where(function (Builder $query) {
			$query->whereNull('email_verified_at')->orWhereNull('phone_verified_at');
		});
		
		if (config('settings.listing_form.listings_review_activation')) {
			$builder->orWhereNull('reviewed_at');
		}
		
		return $builder;
	}
	
	public function scopeArchived(Builder $builder): Builder
	{
		return $builder->whereNotNull('archived_at');
	}
	
	public function scopeUnarchived(Builder $builder): Builder
	{
		return $builder->whereNull('archived_at');
	}
	
	public function scopeReviewed(Builder $builder): Builder
	{
		if (config('settings.listing_form.listings_review_activation')) {
			return $builder->whereNotNull('reviewed_at');
		} else {
			return $builder;
		}
	}
	
	public function scopeUnreviewed(Builder $builder): Builder
	{
		if (config('settings.listing_form.listings_review_activation')) {
			return $builder->whereNull('reviewed_at');
		} else {
			return $builder;
		}
	}
	
	public function scopeWithCountryFix(Builder $builder): Builder
	{
		// Check the Domain Mapping plugin
		if (config('plugins.domainmapping.installed')) {
			return $builder->where('country_code', config('country.code'));
		} else {
			return $builder;
		}
	}

	public function scopeValid(Builder $builder): Builder
	{
		// Check if there are valid payments for this post
		return $builder->whereHas('payments', function ($query) {
			$query->valid()->where('active', 1);
		});
	}
	
	/*
	|--------------------------------------------------------------------------
	| ACCESSORS | MUTATORS
	|--------------------------------------------------------------------------
	*/
	protected function reference(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				$value = $this->id ?? null;
				if (empty($value)) {
					return $value;
				}
				
				return hashId($value, false, false);
			},
		);
	}
	
	protected function visitsFormatted(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				$number = (int)($this->visits ?? 0);
				$shortNumber = Num::short($number);
				
				$value = $shortNumber;
				$value .= ' ';
				$value .= trans_choice('global.count_views', getPlural($number), [], config('app.locale'));
				
				return $value;
			},
		);
	}
	
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
	
	protected function updatedAt(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				$value = new Carbon($value);
				$value->timezone(Date::getAppTimeZone());
				
				return $value;
			},
		);
	}
	
	protected function deletedAt(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				if (empty($value)) {
					return null;
				}
				
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
				if (empty($value)) {
					return null;
				}
				
				if (!$value instanceof Carbon) {
					$value = new Carbon($value);
					$value->timezone(Date::getAppTimeZone());
				}
				
				return Date::customFromNow($value);
			},
		);
	}
	
	protected function deletionMailSentAt(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				if (empty($value)) {
					return null;
				}
				
				$value = new Carbon($value);
				$value->timezone(Date::getAppTimeZone());
				
				return $value;
			},
		);
	}
	
	protected function userPhotoUrl(): Attribute
	{
		return Attribute::make(
			get: function () {
				// Default Photo
				$defaultPhotoUrl = imgUrl(config('larapen.media.avatar'));
				
				// If the relation is not loaded through the Eloquent 'with()' method,
				// then don't make additional query (to prevent performance issues).
				if (!$this->relationLoaded('user')) {
					return $defaultPhotoUrl;
				}
				
				$photoUrl = $this->user?->photo_url ?? null;
				
				return !empty($photoUrl) ? $photoUrl : $defaultPhotoUrl;
			},
		);
	}
	
	protected function email(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				if (!$this->relationLoaded('user')) {
					return $value;
				}
				
				if (isAdminPanel() && isDemoDomain()) {
					$isPostOrPutMethod = (in_array(strtolower(request()->method()), ['post', 'put']));
					$isNotFromAuthForm = (!in_array(request()->segment(2), ['password', 'login']));
					if (auth()->check()) {
						if (isset($this->phone_token)) {
							if ($this->phone_token == 'demoFaker') {
								return $value;
							}
						}
						if (!$isPostOrPutMethod && $isNotFromAuthForm) {
							$value = emailPrefixMask($value);
						}
					}
				}
				
				return $value;
			},
		);
	}
	
	protected function phoneCountry(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				$countryCode = $this->country_code ?? config('country.code');
				
				return !empty($value) ? $value : $countryCode;
			},
		);
	}
	
	protected function phone(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				return phoneE164($value, $this->phone_country);
			},
		);
	}
	
	protected function phoneNational(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				$value = !empty($value) ? $value : $this->phone;
				
				return phoneNational($value, $this->phone_country);
			},
		);
	}
	
	protected function phoneIntl(): Attribute
	{
		return Attribute::make(
			get: function () {
				$value = !empty($this->phone_national)
					? $this->phone_national
					: $this->phone;
				
				if ($this->phone_country == config('country.code')) {
					return phoneNational($value, $this->phone_country);
				}
				
				return phoneIntl($value, $this->phone_country);
			},
		);
	}
	
	protected function title(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				$value = mb_ucfirst($value);
				$cleanedValue = RemoveFromString::contactInfo($value, false, true);
				
				if (!$this->relationLoaded('user')) {
					return $cleanedValue;
				}
				
				if (!isAdminPanel()) {
					if (!empty($this->user)) {
						if (!$this->user->hasAllPermissions(Permission::getStaffPermissions())) {
							$value = $cleanedValue;
						}
					} else {
						$value = $cleanedValue;
					}
				}
				
				return $value;
			},
		);
	}
	
	protected function slug(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				$value = (is_null($value) && isset($this->title)) ? $this->title : $value;
				
				$value = stripNonUtf8Chars($value);
				$value = slugify($value);
				
				// To prevent 404 error when the slug starts by a banned slug/prefix,
				// Add a tilde (~) as prefix to it.
				$bannedSlugs = regexSimilarRoutesPrefixes();
				foreach ($bannedSlugs as $bannedSlug) {
					if (str_starts_with($value, $bannedSlug)) {
						$value = '~' . $value;
						break;
					}
				}
				
				return $value;
			},
		);
	}
	
	/*
	 * For API calls, to allow listing sharing
	 */
	protected function url(): Attribute
	{
		return Attribute::make(
			get: function () {
				if (isset($this->id) && isset($this->title)) {
					$path = str_replace(
						['{slug}', '{hashableId}', '{id}'],
						[$this->slug, hashId($this->id), $this->id],
						config('routes.post')
					);
				} else {
					$path = '#';
				}
				
				if (config('plugins.domainmapping.installed')) {
					$url = dmUrl($this->country_code, $path);
				} else {
					$url = url($path);
				}
				
				return $url;
			},
		);
	}
	
	protected function contactName(): Attribute
	{
		return Attribute::make(
			get: fn ($value) => mb_ucwords($value),
		);
	}
	
	protected function description(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				if (isAdminPanel()) {
					return $value;
				}
				
				$cleanedValue = RemoveFromString::contactInfo($value, false, true);
				
				if (!$this->relationLoaded('user')) {
					$value = $cleanedValue;
				} else {
					if (!empty($this->user)) {
						if (!$this->user->hasAllPermissions(Permission::getStaffPermissions())) {
							$value = $cleanedValue;
						}
					} else {
						$value = $cleanedValue;
					}
				}
				
				$apiValue = (doesRequestIsFromWebApp()) ? transformDescription($value) : strStrip(strip_tags($value));
				
				return isFromApi() ? $apiValue : $value;
			},
		);
	}
	
	protected function excerpt(): Attribute
	{
		return Attribute::make(
			get: function () {
				$description = $this->description ?? '';
				$description = strStrip(strip_tags($description));
				
				return str($description)->limit(100);
			},
		);
	}
	
	protected function tags(): Attribute
	{
		return Attribute::make(
			get: fn ($value) => tagCleaner($value, true),
			set: function ($value) {
				if (is_array($value)) {
					$value = implode(',', $value);
				}
				
				return !empty($value) ? mb_strtolower($value) : $value;
			},
		);
	}
	
	protected function countryFlagUrl(): Attribute
	{
		return Attribute::make(
			get: function () {
				return getCountryFlagUrl($this->country_code);
			},
		);
	}
	
	protected function companyName(): Attribute
	{
		return Attribute::make(
			get: fn ($value) => mb_ucwords($value),
		);
	}
	
	protected function companyDescription(): Attribute
	{
		return Attribute::make(
			get: function ($value) {
				if (isAdminPanel()) {
					return $value;
				}
				
				$cleanedValue = RemoveFromString::contactInfo($value, false, true);
				
				if (!$this->relationLoaded('user')) {
					$value = $cleanedValue;
				} else {
					if (!empty($this->user)) {
						if (!$this->user->hasAllPermissions(Permission::getStaffPermissions())) {
							$value = $cleanedValue;
						}
					} else {
						$value = $cleanedValue;
					}
				}
				
				$transformedValue = nl2br(urlsToLinks(mbStrCleaner($value)));
				$apiValue = (doesRequestIsFromWebApp()) ? $transformedValue : strStrip(strip_tags($value));
				
				return isFromApi() ? $apiValue : $value;
			},
		);
	}
	
	protected function logo(): Attribute
	{
		return Attribute::make(
			get: function ($value, $attributes) {
				if (empty($value)) {
					if (isset($attributes['logo'])) {
						$value = $attributes['logo'];
					}
				}
				
				// OLD PATH
				$value = $this->getLogoFromOldPath($value);
				
				// NEW PATH
				$disk = StorageDisk::getDisk();
				if (empty($value) || !$disk->exists($value)) {
					$value = config('larapen.media.picture');
				}
				
				return $value;
			},
			set: fn ($value) => $this->setLogo($value),
		);
	}
	
	protected function logoUrl(): Attribute
	{
		return Attribute::make(
			get: function () {
				return imgUrl(self::getLogo($this->logo), 'picture-md');
			},
		);
	}
	
	protected function logoUrlSmall(): Attribute
	{
		return Attribute::make(
			get: function () {
				return imgUrl(self::getLogo($this->logo), 'picture-sm');
			},
		);
	}
	
	protected function logoUrlMedium(): Attribute
	{
		return Attribute::make(
			get: function () {
				return imgUrl(self::getLogo($this->logo), 'picture-md');
			},
		);
	}
	
	protected function logoUrlLarge(): Attribute
	{
		return Attribute::make(
			get: function () {
				return imgUrl(self::getLogo($this->logo), 'picture-lg');
			},
		);
	}
	
	protected function salaryFormatted(): Attribute
	{
		return Attribute::make(
			get: function () {
				$salaryMin = $this->salary_min ?? 0;
				$salaryMax = $this->salary_max ?? 0;
				
				// Relation with Currency
				$currency = [];
				if ($this->relationLoaded('currency')) {
					if (!empty($this->currency)) {
						$currency = $this->currency->toArray();
					}
				}
				
				if ($salaryMin > 0 || $salaryMax > 0) {
					$valueMin = ($salaryMin > 0) ? Num::money($salaryMin, $currency) : '';
					$valueMax = ($salaryMax > 0) ? Num::money($salaryMax, $currency) : '';
					
					$value = ($salaryMax > 0)
						? (($salaryMin > 0) ? $valueMin . ' - ' . $valueMax : $valueMax)
						: $valueMin;
				} else {
					$value = Num::money('--', $currency);
				}
				
				return $value;
			},
		);
	}
	
	protected function distanceInfo(): Attribute
	{
		return Attribute::make(
			get: function () {
				if (!$this->relationLoaded('city')) {
					return null;
				}
				
				if (!isset($this->distance)) {
					return null;
				}
				
				if (!is_numeric($this->distance)) {
					return null;
				}
				
				return round($this->distance, 2) . getDistanceUnit();
			},
		);
	}
	
	/*
	|--------------------------------------------------------------------------
	| OTHER PRIVATE METHODS
	|--------------------------------------------------------------------------
	*/
	private function setLogo($value)
	{
		// Don't make an upload for Post->logo for logged users
		if (!str_contains(currentRouteAction(), 'Admin\PostController')) {
			if (auth()->check()) {
				return $value;
			}
		}
		
		if (!is_string($value)) {
			return $value;
		}
		
		if ($value == url('/')) {
			return null;
		}
		
		// Retrieve current value without upload a new file
		if (str_starts_with($value, config('larapen.media.logo'))) {
			return null;
		}
		
		// Extract the value's country code
		$matches = [];
		preg_match('#files/([A-Za-z]{2})/\d+#i', $value, $matches);
		$valueCountryCode = (!empty($matches[1])) ? $matches[1] : null;
		
		// Extract the value's ID
		$matches = [];
		preg_match('#files/[A-Za-z]{2}/(\d+)#i', $value, $matches);
		$valueId = (!empty($matches[1])) ? $matches[1] : null;
		
		// Extract the value's filename
		$matches = [];
		preg_match('#files/[A-Za-z]{2}/\d+/(.+)#i', $value, $matches);
		$valueFilename = (!empty($matches[1])) ? $matches[1] : null;
		
		// Destination Path
		if (empty($this->id) || empty($this->country_code)) {
			return null;
		}
		$destPath = 'files/' . strtolower($this->country_code) . '/' . $this->id;
		
		if (!empty($valueCountryCode) && !empty($valueId) && !empty($valueFilename)) {
			// Value's Path
			$valueDestinationPath = 'files/' . strtolower($valueCountryCode) . '/' . $valueId;
			if ($valueDestinationPath != $destPath) {
				$oldFilePath = $valueDestinationPath . '/' . $valueFilename;
				$newFilePath = $destPath . '/' . $valueFilename;
				
				// Copy the file
				$disk = StorageDisk::getDisk();
				$disk->copy($oldFilePath, $newFilePath);
				
				return $newFilePath;
			}
		}
		
		if (!str_starts_with($value, 'files/')) {
			$value = $destPath . last(explode($destPath, $value));
		}
		
		return $value;
	}
	
	private function getLogoFromOldPath($value): ?string
	{
		// Fix path
		$oldBase = 'pictures/';
		$newBase = 'files/';
		if (str_contains($value, $oldBase)) {
			$value = $newBase . last(explode($oldBase, $value));
		}
		
		return $value;
	}
}
