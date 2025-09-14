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

namespace App\Models\Traits;

use App\Helpers\Files\Storage\StorageDisk;
use App\Helpers\UrlGen;
use App\Models\Post;
use Spatie\Feed\FeedItem;

trait PostTrait
{
	// ===| ADMIN PANEL METHODS |===
	
	public function getTitleHtml(): string
	{
		$out = getPostUrl($this);
		
		if (!empty($this->archived_at)) {
			$out .= '<br>';
			$out .= '<span class="badge bg-secondary">';
			$out .= trans('admin.Archived');
			$out .= '</span>';
		}
		
		return $out;
	}
	
	public function getLogoHtml(): string
	{
		$style = ' style="width:auto; max-height:90px;"';
		
		// Get logo
		$logoUrl = $this->logo_url_small ?? config('larapen.media.picture');
		$out = '<img src="' . $logoUrl . '" data-bs-toggle="tooltip" title="' . $this->title . '"' . $style . '>';
		
		// Add a link to the listing
		$url = dmUrl($this->country_code, UrlGen::postPath($this));
		
		return '<a href="' . $url . '" target="_blank">' . $out . '</a>';
	}
	
	public function getPictureHtml(): string
	{
		// Get ad URL
		// $url = url(UrlGen::postUri($this));
		$url = dmUrl($this->country_code, UrlGen::postPath($this));
		
		// Get the first picture
		$style = ' style="width:auto; max-height:90px;"';
		$pictureUrl = $this->picture_url_small ?? config('larapen.media.picture');
		$out = '<img src="' . $pictureUrl . '" data-bs-toggle="tooltip" title="' . $this->title . '"' . $style . ' class="img-rounded">';
		
		// Add a link to the listing
		return '<a href="' . $url . '" target="_blank">' . $out . '</a>';
	}
	
	public function getCompanyNameHtml(): string
	{
		$companyName = $this->company_name ?? 'Company Name';
		$userName = $this->contact_name ?? 'Guest';
		
		$out = '';
		
		// Company Name
		$out .= $companyName;
		
		// User Name
		$out .= '<br>';
		$out .= '<small>';
		$out .= trans('admin.By_') . ' ';
		if (!empty($this->user)) {
			$url = admin_url('users/' . $this->user->getKey() . '/edit');
			$tooltip = ' data-bs-toggle="tooltip" title="' . $this->user->name . '"';
			
			$out .= '<a href="' . $url . '"' . $tooltip . '>';
			$out .= $userName;
			$out .= '</a>';
		} else {
			$out .= $userName;
		}
		$out .= '</small>';
		
		return $out;
	}
	
	public function getCityHtml(): string
	{
		$out = $this->getCountryHtml();
		$out .= ' - ';
		if (!empty($this->city)) {
			$out .= '<a href="' . UrlGen::city($this->city) . '" target="_blank">' . $this->city->name . '</a>';
		} else {
			$out .= $this->city_id ?? 0;
		}
		
		return $out;
	}
	
	public function getReviewedHtml(): string
	{
		return ajaxCheckboxDisplay($this->{$this->primaryKey}, $this->getTable(), 'reviewed_at', $this->reviewed_at);
	}
	
	public function getFeaturedHtml(): string
	{
		$out = '-';
		if (config('plugins.offlinepayment.installed')) {
			$opTool = '\extras\plugins\offlinepayment\app\Helpers\OpTools';
			if (class_exists($opTool)) {
				$out = $opTool::featuredCheckboxDisplay($this->{$this->primaryKey}, $this->getTable(), 'featured', $this->featured);
			}
		}
		
		return $out;
	}
	
	// ===| OTHER METHODS |===
	
	public static function getFeedItems()
	{
		$cacheExpiration = (int)config('settings.optimization.cache_expiration', 86400);
		$perPage = (int)config('settings.pagination.per_page', 50);
		
		$countryCode = null;
		if (request()->filled('country') || config('plugins.domainmapping.installed')) {
			$countryCode = config('country.code');
			if (!config('plugins.domainmapping.installed')) {
				if (request()->filled('country')) {
					$countryCode = request()->input('country');
				}
			}
		}
		
		// Cache ID
		$cacheId = (!empty($countryCode)) ? $countryCode . '.' : '';
		$cacheId .= 'postModel.getFeedItems';
		
		return cache()->remember($cacheId, $cacheExpiration, function () use ($countryCode, $perPage) {
			$posts = Post::reviewed()
				->unarchived()
				->when(!empty($countryCode), fn ($query) => $query->where('country_code', $countryCode))
				->take($perPage)
				->orderByDesc('id');
			
			return $posts->get();
		});
	}
	
	public function toFeedItem(): FeedItem
	{
		$title = $this->title;
		$title .= (!empty($this->city)) ? ' - ' . $this->city->name : '';
		$title .= (!empty($this->country)) ? ', ' . $this->country->name : '';
		// $summary = str_limit(strStrip(strip_tags($this->description)), 5000);
		$summary = transformDescription($this->description);
		$link = UrlGen::postUri($this, true);
		
		return FeedItem::create()
			->id($link)
			->title($title)
			->summary($summary)
			->category($this?->category?->name ?? '')
			->updated($this->updated_at)
			->link($link)
			->authorName($this->contact_name);
	}
	
	public static function getLogo($value)
	{
		if (empty($value)) {
			return $value;
		}
		
		$disk = StorageDisk::getDisk();
		
		// OLD PATH
		$oldBase = 'pictures/';
		$newBase = 'files/';
		if (str_contains($value, $oldBase)) {
			$value = $newBase . last(explode($oldBase, $value));
		}
		
		// NEW PATH
		if (str_ends_with($value, '/')) {
			return $value;
		}
		
		if (!$disk->exists($value)) {
			$value = config('larapen.media.picture');
		}
		
		return $value;
	}
}
