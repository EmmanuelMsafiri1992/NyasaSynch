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

namespace App\Console\Commands;

// Increase the server resources
$iniConfigFile = __DIR__ . '/../../Helpers/Functions/ini.php';
if (file_exists($iniConfigFile)) {
	include_once $iniConfigFile;
}

use Database\Seeders\SiteInfoSeeder;
use Illuminate\Console\Command;

/*
 * USAGE:
 * php artisan db:seed-site-info '{"settings":{"app":{},"localization":{},"mail":{}},"user":{}}'
 * Artisan::call('db:seed-site-info', ['data' => '{"settings":{"app":{},"localization":{},"mail":{}},"user":{}}']);
 */

class SiteInfoCommand extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'db:seed-site-info {data}';
	
	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Run the fresh install seeder with array data.';
	
	/**
	 * Execute the console command.
	 *
	 * @throws \App\Exceptions\Custom\CustomException
	 */
	public function handle()
	{
		$jsonData = $this->argument('data');
		
		// Convert the input data to array
		$data = json_decode($jsonData, true);
		
		// Run the seeder
		$seeder = new SiteInfoSeeder();
		$seeder->run($data);
	}
}
