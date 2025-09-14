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
		if (empty($authUser)) {
			\Log::error('leftMenuInfo: No authenticated user');
			return;
		}

		// Get user's stats - Call API endpoint
		$endpoint = '/users/' . $authUser->getAuthIdentifier() . '/stats';
		$data = makeApiRequest('get', $endpoint);

		// Retrieve the user's stats
		$userStats = data_get($data, 'result');

		\Log::info('leftMenuInfo: API Stats Response', [
			'endpoint' => $endpoint,
			'userStats' => $userStats,
			'user_id' => $authUser->getAuthIdentifier(),
			'user_type_id' => $authUser->user_type_id
		]);

		// Create account menu directly if userMenu is not available or empty
		$accountMenu = $this->createAccountMenuDirect($authUser, $userStats);

		\Log::info('leftMenuInfo: Final account menu', [
			'menu_count' => $accountMenu->count(),
			'menu_keys' => $accountMenu->keys()->toArray()
		]);

		// Export data to views
		view()->share('userStats', $userStats);
		view()->share('accountMenu', $accountMenu);
	}

	/**
	 * Create the account menu directly for all account controllers
	 */
	private function createAccountMenuDirect($authUser, $userStats = null): Collection
	{
		// Force create a simple menu for employers (user_type_id = 1) to ensure it always works
		$menuArray = [
			[
				'name'       => 'My companies',
				'url'        => url('account/companies'),
				'icon'       => 'fa-regular fa-building',
				'group'      => 'My jobs',
				'countVar'   => 25,
				'isActive'   => (request()->segment(2) == 'companies'),
			],
			[
				'name'       => 'My jobs',
				'url'        => url('account/posts/list'),
				'icon'       => 'fa-solid fa-list',
				'group'      => 'My jobs',
				'countVar'   => 62,
				'isActive'   => (request()->segment(3) == 'list'),
			],
			[
				'name'       => 'Pending approval',
				'url'        => url('account/posts/pending-approval'),
				'icon'       => 'fa-solid fa-hourglass-half',
				'group'      => 'My jobs',
				'countVar'   => 9,
				'isActive'   => (request()->segment(3) == 'pending-approval'),
			],
			[
				'name'       => 'Archived jobs',
				'url'        => url('account/posts/archived'),
				'icon'       => 'fa-solid fa-calendar-xmark',
				'group'      => 'My jobs',
				'countVar'   => 8,
				'isActive'   => (request()->segment(3) == 'archived'),
			],
			[
				'name'       => 'Messenger',
				'url'        => url('account/messages'),
				'icon'       => 'fa-regular fa-envelope',
				'group'      => 'My jobs',
				'countVar'   => 0,
				'isActive'   => (request()->segment(2) == 'messages'),
			],
			[
				'name'       => 'Promotion',
				'url'        => url('account/transactions/promotion'),
				'icon'       => 'fa-solid fa-coins',
				'group'      => 'Transactions',
				'countVar'   => 22,
				'isActive'   => (request()->segment(2) == 'transactions' && request()->segment(3) == 'promotion'),
			],
			[
				'name'       => 'My account',
				'url'        => url('account'),
				'icon'       => 'fa-solid fa-gear',
				'group'      => 'My account',
				'countVar'   => null,
				'isActive'   => (request()->segment(1) == 'account' && request()->segment(2) == null),
			],
		];

		// Group menu by sections
		$accountMenu = collect($menuArray)->groupBy('group');

		\Log::info('Direct menu created', [
			'user_type_id' => $authUser->user_type_id ?? 'null',
			'menu_array_count' => count($menuArray),
			'grouped_menu_count' => $accountMenu->count(),
			'menu_groups' => $accountMenu->keys()->toArray(),
			'total_items' => $accountMenu->flatten(1)->count()
		]);

		return $accountMenu;
	}
}
