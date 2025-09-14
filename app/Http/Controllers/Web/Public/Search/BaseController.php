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

namespace App\Http\Controllers\Web\Public\Search;

use App\Http\Controllers\Web\Public\FrontController;
use App\Http\Controllers\Web\Public\Search\Traits\MetaTagTrait;
use App\Http\Controllers\Web\Public\Search\Traits\TitleTrait;
use App\Http\Requests\Front\SendPostByEmailRequest;
use Illuminate\Http\Request;

class BaseController extends FrontController
{
	use MetaTagTrait, TitleTrait;
	
	public $request;
	
	/**
	 * SearchController constructor.
	 *
	 * @param Request $request
	 */
	public function __construct(Request $request)
	{
		parent::__construct();
		
		$this->request = $request;
	}
	
	/**
	 * @param array|null $sidebar
	 * @return void
	 */
	protected function bindSidebarVariables(?array $sidebar = []): void
	{
		if (!empty($sidebar)) {
			foreach ($sidebar as $key => $value) {
				view()->share($key, $value);
			}
		}
	}
	
	/**
	 * Set the Open Graph info
	 *
	 * @param $og
	 * @param $title
	 * @param $description
	 * @param array|null $apiExtra
	 * @return void
	 */
	protected function setOgInfo($og, $title, $description, ?array $apiExtra = null): void
	{
		$og->title($title)->description($description)->type('website');
		
		if (!is_array($apiExtra) || (int)data_get($apiExtra, 'count.0') > 0) {
			if ($og->has('image')) {
				$og->forget('image')->forget('image:width')->forget('image:height');
			}
		}
		
		view()->share('og', $og);
	}
	
	/**
	 * Send Post by Email.
	 *
	 * @param \App\Http\Requests\Front\SendPostByEmailRequest $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function sendByEmail(SendPostByEmailRequest $request)
	{
		$postId = $request->input('post_id');
		
		// Call API endpoint
		$endpoint = '/posts/' . $postId . '/sendByEmail';
		$data = makeApiRequest('post', $endpoint, $request->all());
		
		// Parsing the API response
		$message = data_get($data, 'message', t('unknown_error'));
		
		// HTTP Error Found
		if (!data_get($data, 'isSuccessful')) {
			flash($message)->error();
			
			return redirect()->back()->withInput();
		}
		
		// Notification Message
		if (data_get($data, 'success')) {
			flash($message)->success();
		} else {
			flash($message)->error();
		}
		
		return redirect()->to(url()->previous());
	}
}
