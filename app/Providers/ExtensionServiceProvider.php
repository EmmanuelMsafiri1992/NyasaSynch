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

namespace App\Providers;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Spatie\Dropbox\Client as DropboxClient;
use Spatie\FlysystemDropbox\DropboxAdapter;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

class ExtensionServiceProvider extends ServiceProvider
{
	/**
	 * Bootstrap any application services.
	 *
	 * @return void
	 */
	public function boot(): void
	{
		// Filesystem Adapter for: Dropbox
		Storage::extend('dropbox', function ($app, $config) {
			$client = new DropboxClient($config['authorization_token']);
			$adapter = new DropboxAdapter($client);
			
			return new FilesystemAdapter(
				new Filesystem($adapter, $config),
				$adapter,
				$config
			);
		});
		
		// Additional Symfony Transports
		// Symfony Transport for: Brevo
		Mail::extend('brevo', function () {
			return (new BrevoTransportFactory)->create(
				new Dsn(
					'brevo+api',
					'default',
					config('services.brevo.key')
				)
			);
		});
	}
}
