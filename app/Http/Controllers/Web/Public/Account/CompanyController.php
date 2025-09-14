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

namespace App\Http\Controllers\Web\Public\Account;

use App\Http\Requests\Front\CompanyRequest;
use Larapen\LaravelMetaTags\Facades\MetaTag;

class CompanyController extends AccountBaseController
{
	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Contracts\View\View
	 */
	public function index()
	{
		// Call API endpoint
		$endpoint = '/companies';
		$queryParams = [
			'belongLoggedUser' => true,
			'countPosts'       => true,
			'q'                => request()->input('q'),
			'sort'             => 'created_at',
		];
		$queryParams = array_merge(request()->all(), $queryParams);
		$data = makeApiRequest('get', $endpoint, $queryParams);
		
		$apiMessage = $this->handleHttpError($data);
		$apiResult = data_get($data, 'result');
		
		$appName = config('settings.app.name', 'Site Name');
		$title = t('my_companies_list') . ' - ' . $appName;
		
		// Meta Tags
		MetaTag::set('title', $title);
		MetaTag::set('description', t('my_companies_list_on', ['appName' => config('settings.app.name')]));
		
		return appView('account.company.index', compact('apiResult', 'apiMessage'));
	}
	
	/**
	 * Show the form for creating a new resource.
	 */
	public function create()
	{
		// Meta Tags
		MetaTag::set('title', t('Create a new company'));
		MetaTag::set('description', t('Create a new company on', ['appName' => config('settings.app.name')]));
		
		return appView('account.company.create');
	}
	
	/**
	 * Store a newly created resource in storage.
	 *
	 * @param CompanyRequest $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function store(CompanyRequest $request)
	{
		// Call API endpoint
		$endpoint = '/companies';
		$data = makeApiRequest('post', $endpoint, $request->all(), [], true);
		
		// Parsing the API response
		$message = data_get($data, 'message', t('unknown_error'));
		
		// HTTP Error Found
		if (!data_get($data, 'isSuccessful')) {
			return back()->withErrors(['error' => $message])->withInput();
		}
		
		// Notification Message
		if (data_get($data, 'success')) {
			flash($message)->success();
		} else {
			flash($message)->error();
		}
		
		$pathTo = 'account/companies';
		$id = data_get($data, 'result.id');
		if (!empty($id)) {
			$pathTo = 'account/companies/' . $id . '/edit';
		}
		
		return redirect()->to($pathTo);
	}
	
	/**
	 * Display the specified resource.
	 *
	 * @param $id
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function show($id)
	{
		return redirect()->to('account/companies/' . $id . '/edit');
	}
	
	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param $id
	 * @return \Illuminate\Contracts\View\View
	 */
	public function edit($id)
	{
		// Call API endpoint
		$endpoint = '/companies/' . $id;
		$queryParams = [
			'embed'            => 'city,subAdmin1,subAdmin2',
			'belongLoggedUser' => true,
		];
		$queryParams = array_merge(request()->all(), $queryParams);
		$data = makeApiRequest('get', $endpoint, $queryParams);
		
		$message = $this->handleHttpError($data);
		$company = data_get($data, 'result');
		
		abort_if(empty($company), 404, $message ?? t('company_not_found'));
		
		// Meta Tags
		MetaTag::set('title', t('Edit the Company'));
		MetaTag::set('description', t('Edit the Company on', ['appName' => config('settings.app.name')]));
		
		return appView('account.company.edit', compact('company'));
	}
	
	/**
	 * Update the specified resource in storage.
	 *
	 * @param $id
	 * @param CompanyRequest $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function update($id, CompanyRequest $request)
	{
		// Call API endpoint
		$endpoint = '/companies/' . $id;
		$data = makeApiRequest('put', $endpoint, $request->all(), [], true);
		
		// Parsing the API response
		$message = data_get($data, 'message', t('unknown_error'));
		
		// HTTP Error Found
		if (!data_get($data, 'isSuccessful')) {
			return back()->withErrors(['error' => $message])->withInput();
		}
		
		// Notification Message
		if (data_get($data, 'success')) {
			flash($message)->success();
		} else {
			flash($message)->error();
		}
		
		return redirect()->to('account/companies/' . $id . '/edit');
	}
	
	/**
	 * Remove the specified resource from storage.
	 *
	 * @param null $id
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function destroy($id = null)
	{
		// Get Entries ID
		$ids = [];
		if (request()->filled('entries')) {
			$ids = request()->input('entries');
		} else {
			if (isStringable($id) && !empty($id)) {
				$ids[] = (string)$id;
			}
		}
		$ids = implode(',', $ids);
		
		// Call API endpoint
		$endpoint = '/companies/' . $ids;
		$data = makeApiRequest('delete', $endpoint, request()->all());
		
		// Parsing the API response
		$message = data_get($data, 'message', t('unknown_error'));
		
		// HTTP Error Found
		if (!data_get($data, 'isSuccessful')) {
			return back()->withErrors(['error' => $message])->withInput();
		}
		
		// Notification Message
		if (data_get($data, 'success')) {
			flash($message)->success();
		} else {
			flash($message)->error();
		}
		
		return redirect()->to('account/companies');
	}
}
