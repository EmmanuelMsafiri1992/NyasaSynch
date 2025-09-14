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

use App\Http\Controllers\Web\Public\FrontController;
use Illuminate\Support\Collection;

abstract class AccountBaseController extends FrontController
{
	/**
	 * AccountBaseController constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		
		if (auth()->check()) {
			$this->leftMenuInfo();
		}
		
		// Get Page Current Path
		$pagePath = (request()->segment(1) == 'account') ? (request()->segment(3) ?? '') : '';
		view()->share('pagePath', $pagePath);
	}
	
	public function leftMenuInfo(): void
	{
		$authUser = auth()->user();
		if (empty($authUser)) return;

		// Get user's stats - Call API endpoint
		$endpoint = '/users/' . $authUser->getAuthIdentifier() . '/stats';
		$data = makeApiRequest('get', $endpoint);

		// Retrieve the user's stats
		$userStats = data_get($data, 'result');

		// Create account menu directly if userMenu is not available or empty
		$accountMenu = $this->createAccountMenuDirect($authUser, $userStats);

		// Export data to views
		view()->share('userStats', $userStats);
		view()->share('accountMenu', $accountMenu);
	}

	/**
	 * Create the account menu directly for all account controllers
	 */
	private function createAccountMenuDirect($authUser, $userStats = null): Collection
	{
		// Create the menu structure - default to employer menu, with fallback for missing user_type_id
		$menuArray = [];

		// Default employer menu (or fallback menu if user_type_id is missing)
		if (empty($authUser->user_type_id) || $authUser->user_type_id == 1) {
			$menuArray = [
				[
					'name'       => t('My companies'),
					'url'        => url('account/companies'),
					'icon'       => 'fa-regular fa-building',
					'group'      => t('my_listings'),
					'countVar'   => data_get($userStats, 'companies', 0),
					'isActive'   => (request()->segment(2) == 'companies'),
				],
				[
					'name'       => t('my_listings'),
					'url'        => url('account/posts/list'),
					'icon'       => 'fa-solid fa-list',
					'group'      => t('my_listings'),
					'countVar'   => data_get($userStats, 'posts.published', 0),
					'isActive'   => (request()->segment(3) == 'list'),
				],
				[
					'name'       => t('pending_approval'),
					'url'        => url('account/posts/pending-approval'),
					'icon'       => 'fa-solid fa-hourglass-half',
					'group'      => t('my_listings'),
					'countVar'   => data_get($userStats, 'posts.pendingApproval', 0),
					'isActive'   => (request()->segment(3) == 'pending-approval'),
				],
				[
					'name'       => t('archived_ads'),
					'url'        => url('account/posts/archived'),
					'icon'       => 'fa-solid fa-calendar-xmark',
					'group'      => t('my_listings'),
					'countVar'   => data_get($userStats, 'posts.archived', 0),
					'isActive'   => (request()->segment(3) == 'archived'),
				],
				[
					'name'       => t('messenger'),
					'url'        => url('account/messages'),
					'icon'       => 'fa-regular fa-envelope',
					'group'      => t('my_listings'),
					'countVar'   => data_get($userStats, 'threads.all', 0),
					'isActive'   => (request()->segment(2) == 'messages'),
				],
				[
					'name'       => t('promotion'),
					'url'        => url('account/transactions/promotion'),
					'icon'       => 'fa-solid fa-coins',
					'group'      => 'Transactions',
					'countVar'   => data_get($userStats, 'transactions.promotion', 0),
					'isActive'   => (request()->segment(2) == 'transactions' && request()->segment(3) == 'promotion'),
				],
			];
		}

		// For job seekers (user_type_id = 2)
		if ($authUser->user_type_id == 2) {
			$menuArray = [
				[
					'name'       => t('My resumes'),
					'url'        => url('account/resumes'),
					'icon'       => 'fa-solid fa-paperclip',
					'group'      => t('my_listings'),
					'countVar'   => data_get($userStats, 'resumes', 0),
					'isActive'   => (request()->segment(2) == 'resumes'),
				],
				[
					'name'       => t('Favourite jobs'),
					'url'        => url('account/posts/favourite'),
					'icon'       => 'fa-solid fa-bookmark',
					'group'      => t('my_listings'),
					'countVar'   => data_get($userStats, 'posts.favourite', 0),
					'isActive'   => (request()->segment(3) == 'favourite'),
				],
				[
					'name'       => t('Saved searches'),
					'url'        => url('account/saved-searches'),
					'icon'       => 'fa-solid fa-bell',
					'group'      => t('my_listings'),
					'countVar'   => data_get($userStats, 'savedSearch', 0),
					'isActive'   => (request()->segment(2) == 'saved-searches'),
				],
				[
					'name'       => t('messenger'),
					'url'        => url('account/messages'),
					'icon'       => 'fa-regular fa-envelope',
					'group'      => t('my_listings'),
					'countVar'   => data_get($userStats, 'threads.all', 0),
					'isActive'   => (request()->segment(2) == 'messages'),
				],
			];
		}

		// Always add My Account section for all user types
		$menuArray[] = [
			'name'       => t('my_account'),
			'url'        => url('account'),
			'icon'       => 'fa-solid fa-gear',
			'group'      => t('my_account'),
			'countVar'   => null,
			'isActive'   => (request()->segment(1) == 'account' && request()->segment(2) == null),
		];

		// Ensure we always have some menu items
		if (empty($menuArray)) {
			$menuArray[] = [
				'name'       => t('my_account'),
				'url'        => url('account'),
				'icon'       => 'fa-solid fa-gear',
				'group'      => t('my_account'),
				'countVar'   => null,
				'isActive'   => true,
			];
		}

		// Group menu by sections
		$accountMenu = collect($menuArray)->groupBy('group');

		// Debug: Log menu creation
		\Log::info('Account menu created', [
			'user_type_id' => $authUser->user_type_id ?? 'null',
			'menu_groups' => $accountMenu->keys()->toArray(),
			'total_items' => $accountMenu->flatten(1)->count()
		]);

		return $accountMenu;
	}
}
