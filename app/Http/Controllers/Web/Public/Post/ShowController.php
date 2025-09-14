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

namespace App\Http\Controllers\Web\Public\Post;

use App\Helpers\UrlGen;
use App\Http\Controllers\Web\Public\Post\Traits\CatBreadcrumbTrait;
use App\Models\Package;
use App\Http\Controllers\Web\Public\FrontController;
use Larapen\LaravelMetaTags\Facades\MetaTag;

class ShowController extends FrontController
{
	use CatBreadcrumbTrait;
	
	public function __construct()
	{
		parent::__construct();
		
		$this->commonQueries();
	}
	
	/**
	 * @return void
	 */
	public function commonQueries(): void
	{
		// Count Packages
		$countPackages = Package::applyCurrency()->count();
		view()->share('countPackages', $countPackages);
		
		// Count Payment Methods
		view()->share('countPaymentMethods', $this->countPaymentMethods);
	}
	
	/**
	 * Show the Post's Details.
	 *
	 * @param $postId
	 * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
	 * @throws \Exception
	 */
	public function index($postId)
	{
		// Get and Check the Controller's Method Parameters
		$parameters = request()->route()->parameters();
		
		// Check if the Post's ID key exists
		$idKey = array_key_exists('hashableId', $parameters) ? 'hashableId' : 'id';
		$idKeyDoesNotExist = (
			empty($parameters[$idKey])
			|| (!isHashedId($parameters[$idKey]) && !is_numeric($parameters[$idKey]))
		);
		
		// Show 404 error if the Post's ID key cannot be found
		abort_if($idKeyDoesNotExist, 404);
		
		// Set the Parameters
		$postId = $parameters[$idKey];
		$slug = $parameters['slug'] ?? null;
		
		// Forcing redirection 301 for hashed (or non-hashed) ID to update links in search engine indexes
		if (config('settings.seo.listing_hashed_id_seo_redirection')) {
			if (config('settings.seo.listing_hashed_id_enabled') && !isHashedId($postId) && is_numeric($postId)) {
				// Don't lose important notification, so we need to persist your flash data for the request (the redirect request)
				request()->session()->reflash();
				
				$uri = UrlGen::postPathBasic(hashId($postId), $slug);
				
				return redirect()->to($uri, 301)->withHeaders(config('larapen.core.noCacheHeaders'));
			}
			if (!config('settings.seo.listing_hashed_id_enabled') && isHashedId($postId) && !is_numeric($postId)) {
				// Don't lose important notification, so we need to persist your flash data for the request (the redirect request)
				request()->session()->reflash();
				
				$uri = UrlGen::postPathBasic(hashId($postId, true), $slug);
				
				return redirect()->to($uri, 301)->withHeaders(config('larapen.core.noCacheHeaders'));
			}
		}
		
		// Decode Hashed ID
		$postId = hashId($postId, true) ?? $postId;
		
		// Call API endpoint
		$endpoint = '/posts/' . $postId;
		$queryParams = [
			'detailed' => 1,
		];
		$queryParams = array_merge(request()->all(), $queryParams);
		$headers = session()->has('postIsVisited') ? ['X-VISITED-BY-SAME-SESSION' => $postId] : [];
		$data = makeApiRequest('get', $endpoint, $queryParams, $headers);
		
		$message = $this->handleHttpError($data);
		$post = data_get($data, 'result');
		
		// Listing isn't found
		abort_if(empty($post), 404, $message ?? t('post_not_found'));
		
		session()->put('postIsVisited', $postId);
		
		// Get possible post's registered Author (That's NOT the logged user)
		$user = data_get($post, 'user');
		
		// Get the logged user's resumes
		$resumes = $this->getLoggedUserResumes();
		$totalResumes = count($resumes);
		
		// Get the user's latest résumé
		$lastResume = $resumes[array_key_first($resumes)] ?? [];
		
		// Get post's user decision about comments activation
		$commentsAreDisabledByUser = (data_get($user, 'disable_comments') == 1);
		
		// Category Breadcrumb
		$catBreadcrumb = $this->getCatBreadcrumb(data_get($post, 'category'), 1);
		
		// GET SIMILAR POSTS
		$widgetSimilarPosts = $this->similarPosts(data_get($post, 'id'));
		
		$isFromPostDetails = currentRouteActionContains('Post\ShowController');
		
		// Meta Tags
		[$title, $description, $keywords] = getMetaTag('listingDetails');
		$title = str_replace('{ad.title}', data_get($post, 'title'), $title);
		$title = str_replace('{location.name}', data_get($post, 'city.name'), $title);
		$description = str_replace('{ad.description}', str(strStrip(strip_tags(data_get($post, 'description'))))->limit(200), $description);
		$keywords = str_replace('{ad.tags}', str_replace(',', ', ', @implode(',', data_get($post, 'tags'))), $keywords);
		
		$title = removeUnmatchedPatterns($title);
		$description = removeUnmatchedPatterns($description);
		$keywords = removeUnmatchedPatterns($keywords);
		
		// Fallback
		if (empty($title)) {
			$title = data_get($post, 'title') . ', ' . data_get($post, 'city.name');
		}
		if (empty($description)) {
			$description = str(strStrip(strip_tags(data_get($post, 'description'))))->limit(200);
		}
		
		MetaTag::set('title', $title);
		MetaTag::set('description', $description);
		MetaTag::set('keywords', $keywords);
		
		// Open Graph
		$this->og->title($title)->description($description)->type('article');
		if (!empty(data_get($post, 'logo_url_big'))) {
			if ($this->og->has('image')) {
				$this->og->forget('image')->forget('image:width')->forget('image:height');
			}
			$this->og->image(data_get($post, 'logo_url_big'), [
				'width'  => (int)config('settings.seo.og_image_width', 1200),
				'height' => (int)config('settings.seo.og_image_height', 630),
			]);
		}
		view()->share('og', $this->og);
		
		return appView(
			'post.show.index',
			compact(
				'post',
				'user',
				'catBreadcrumb',
				'resumes',
				'totalResumes',
				'lastResume', // <--- Required in a job apply form
				'commentsAreDisabledByUser',
				'widgetSimilarPosts',
				'isFromPostDetails'
			)
		);
	}
	
	/**
	 * @param $postId
	 * @return array|null
	 */
	public function similarPosts($postId): ?array
	{
		$post = null;
		$posts = [];
		$totalPosts = 0;
		$widgetSimilarPosts = null;
		$message = null;
		
		// GET SIMILAR POSTS
		if (in_array(config('settings.listing_page.similar_listings'), ['1', '2'])) {
			// Call API endpoint
			$endpoint = '/posts';
			$queryParams = [
				'op'       => 'similar',
				'postId'   => $postId,
				'distance' => 50, // km OR miles
			];
			$queryParams = array_merge(request()->all(), $queryParams);
			$headers = [
				'X-WEB-CONTROLLER' => class_basename(get_class($this)),
			];
			$data = makeApiRequest('get', $endpoint, $queryParams, $headers);
			
			$message = data_get($data, 'message');
			$posts = data_get($data, 'result.data');
			$totalPosts = data_get($data, 'extra.count.0');
			$post = data_get($data, 'extra.preSearch.post');
		}
		
		if (config('settings.listing_page.similar_listings') == '1') {
			// Featured Area Data
			$widgetSimilarPosts = [
				'title'      => t('Similar Jobs'),
				'link'       => UrlGen::category(data_get($post, 'category')),
				'posts'      => $posts,
				'totalPosts' => $totalPosts,
				'message'    => $message,
			];
			$widgetSimilarPosts = ($totalPosts > 0) ? $widgetSimilarPosts : null;
		} else if (config('settings.listing_page.similar_listings') == '2') {
			$distance = 50; // km OR miles
			
			// Featured Area Data
			$widgetSimilarPosts = [
				'title'      => t('more_jobs_at_x_distance_around_city', [
					'distance' => $distance,
					'unit'     => getDistanceUnit(config('country.code')),
					'city'     => data_get($post, 'city.name'),
				]),
				'link'       => UrlGen::city(data_get($post, 'city')),
				'posts'      => $posts,
				'totalPosts' => $totalPosts,
				'message'    => $message,
			];
			$widgetSimilarPosts = ($totalPosts > 0) ? $widgetSimilarPosts : null;
		}
		
		return $widgetSimilarPosts;
	}
	
	/**
	 * Get the logged user's resumes in view
	 *
	 * @return array
	 */
	private function getLoggedUserResumes(): array
	{
		// Call API endpoint
		$endpoint = '/resumes';
		$queryParams = [
			'belongLoggedUser' => true,
			'forApplyingJob'   => true,
			'sort'             => 'created_at',
		];
		$queryParams = array_merge(request()->all(), $queryParams);
		$data = makeApiRequest('get', $endpoint, $queryParams);
		
		if (!data_get($data, 'isSuccessful')) {
			return [];
		}
		
		$apiResult = data_get($data, 'result');
		
		return (array)data_get($apiResult, 'data');
	}
}
