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

namespace App\Providers\AppService\ConfigTrait;

trait OptimizationConfig
{
	private function updateOptimizationConfig(?array $settings = []): void
	{
		config()->set('cache.default', env('CACHE_STORE', 'file'));
		
		// Memcached
		config()->set('cache.stores.memcached.persistent_id', env('MEMCACHED_PERSISTENT_ID'));
		config()->set('cache.stores.memcached.sasl', [
			env('MEMCACHED_USERNAME'),
			env('MEMCACHED_PASSWORD'),
		]);
		$memcachedServers = [];
		$i = 1;
		while (getenv('MEMCACHED_SERVER_' . $i . '_HOST')) {
			if ($i == 1) {
				$host = '127.0.0.1';
				$port = 11211;
			} else {
				$host = null;
				$port = null;
			}
			$memcachedServers[$i]['host'] = env('MEMCACHED_SERVER_' . $i . '_HOST', $host);
			$memcachedServers[$i]['port'] = env('MEMCACHED_SERVER_' . $i . '_PORT', $port);
			$i++;
		}
		config()->set('cache.stores.memcached.servers', $memcachedServers);
		
		// Check if the caching is disabled, then disabled it!
		if (config('settings.optimization.cache_driver') == 'array') {
			config()->set('settings.optimization.cache_expiration', '-1');
		}
	}
}
