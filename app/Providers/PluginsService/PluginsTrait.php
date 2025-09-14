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

namespace App\Providers\PluginsService;

use App\Helpers\Arr;

trait PluginsTrait
{
	/**
	 * Load all the installed plugins
	 *
	 * @return void
	 */
	private function loadPlugins(): void
	{
		$plugins = plugin_installed_list();
		$plugins = collect($plugins)
			->map(function ($item, $key) {
				if (is_object($item)) {
					$item = Arr::fromObject($item);
				}
				if (!empty($item['item_id'])) {
					$item['installed'] = plugin_check_purchase_code($item);
				}
				
				return $item;
			})->toArray();
		
		config()->set('plugins', $plugins);
		config()->set('plugins.installed', collect($plugins)->whereStrict('installed', true)->toArray());
	}
}
