<?php

namespace Database\Seeders;

// Increase the server resources
$iniConfigFile = __DIR__ . '/../../app/Helpers/Functions/ini.php';
if (file_exists($iniConfigFile)) {
	include_once $iniConfigFile;
}

use App\Helpers\DBTool;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
	/**
	 * Seed the application's database.
	 */
	public function run(): void
	{
		// Code start execution time
		$startTime = now();
		
		Schema::disableForeignKeyConstraints();
		
		// Truncate all tables
		$prefix = DB::getTablePrefix();
		$tables = DBTool::getDatabaseTables($prefix, false);
		if (count($tables) > 0) {
			foreach ($tables as $table) {
				$rawTable = $prefix . $table;
				
				DB::statement('ALTER TABLE ' . $rawTable . ' AUTO_INCREMENT=1;');
				
				// Don't truncate some tables (eg. migrations, ...)
				if ($table == 'migrations' || $table == 'users') {
					continue;
				}
				
				// Don't truncate the 'blacklist' table in production (or in other environment than local)
				if (!isLocalEnv()) {
					if ($table == 'blacklist') {
						continue;
					}
				}
				
				DB::table($table)->truncate();
			}
		}
		
		// Run Default Seeders
		$this->call(LanguageSeeder::class);
		$this->call(AdvertisingSeeder::class);
		$this->call(CategorySeeder::class);
		$this->call(CurrencySeeder::class);
		$this->call(HomeSectionSeeder::class);
		$this->call(PackageSeeder::class);
		$this->call(PageSeeder::class);
		$this->call(PaymentMethodSeeder::class);
		$this->call(PostTypeSeeder::class);
		$this->call(ReportTypeSeeder::class);
		$this->call(SalaryTypeSeeder::class);
		$this->call(SettingSeeder::class);
		$this->call(CountrySeeder::class);
		
		$isDevOrDemoEnv = (isDevEnv() || isDemoEnv());
		if ($isDevOrDemoEnv) {
			$factoriesSeeders = [
				'\Database\Seeders\Factories\ClearFilesSeeder',
				'\Database\Seeders\Factories\UserSeeder',
				'\Database\Seeders\Factories\PermissionDataSeeder',
				'\Database\Seeders\Factories\SettingDataSeeder',
				'\Database\Seeders\Factories\HomeDataSeeder',
				'\Database\Seeders\Factories\CountryDataSeeder',
				'\Database\Seeders\Factories\LanguageDataSeeder',
				'\Database\Seeders\Factories\MetaTagSeeder',
				'\Database\Seeders\Factories\PageSeeder',
				'\Database\Seeders\Factories\PostSeeder',
				'\Database\Seeders\Factories\FakerSeeder',
				'\Database\Seeders\Factories\MessengerSeeder',
				'\Database\Seeders\Factories\BlacklistSeeder',
			];
			
			foreach ($factoriesSeeders as $seeder) {
				if (str_contains($seeder, 'BlacklistSeeder')) {
					if (isLocalEnv()) {
						continue;
					}
				}
				
				if (class_exists($seeder)) {
					$this->call($seeder);
				}
			}
		}
		
		Schema::enableForeignKeyConstraints();
		
		// Get the code's execution's duration
		$this->execTimeLog($startTime->diffForHumans(now(), CarbonInterface::DIFF_ABSOLUTE, false, 3));
	}
	
	/**
	 * Code Execution Time Log
	 *
	 * @param $message
	 * @return void
	 */
	private function execTimeLog($message)
	{
		$message = 'Execution Time: ' . $message;
		
		$this->command->info($message);
		Log::channel('seeder')->info($message);
	}
}
