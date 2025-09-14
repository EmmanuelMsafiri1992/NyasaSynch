<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LanguageSeeder extends Seeder
{
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run()
	{
		$entries = [
			[
				'code'                  => 'en',
				'locale'                => $this->getUtf8Locale('en_US'),
				'name'                  => 'English',
				'native'                => 'English',
				'flag'                  => 'flag-icon-gb',
				'script'                => 'Latn',
				'direction'             => 'ltr',
				'russian_pluralization' => '0',
				'date_format'           => 'MMM Do, YYYY',
				'datetime_format'       => 'MMM Do, YYYY [at] HH:mm',
				'active'                => '1',
				'default'               => '1',
				'parent_id'             => null,
				'lft'                   => '2',
				'rgt'                   => '3',
				'depth'                 => '0',
				'deleted_at'            => null,
				'created_at'            => now()->format('Y-m-d H:i:s'),
				'updated_at'            => now()->format('Y-m-d H:i:s'),
			],
			[
				'code'                  => 'fr',
				'locale'                => $this->getUtf8Locale('fr_FR'),
				'name'                  => 'French',
				'native'                => 'Français',
				'flag'                  => 'flag-icon-fr',
				'script'                => 'Latn',
				'direction'             => 'ltr',
				'russian_pluralization' => '0',
				'date_format'           => 'Do MMM YYYY',
				'datetime_format'       => 'Do MMM YYYY [à] H[h]mm',
				'active'                => '1',
				'default'               => '0',
				'parent_id'             => null,
				'lft'                   => '4',
				'rgt'                   => '5',
				'depth'                 => '1',
				'deleted_at'            => null,
				'created_at'            => now()->format('Y-m-d H:i:s'),
				'updated_at'            => now()->format('Y-m-d H:i:s'),
			],
			[
				'code'                  => 'es',
				'locale'                => $this->getUtf8Locale('es_ES'),
				'name'                  => 'Spanish',
				'native'                => 'Español',
				'flag'                  => 'flag-icon-es',
				'script'                => 'Latn',
				'direction'             => 'ltr',
				'russian_pluralization' => '0',
				'date_format'           => 'D [de] MMMM [de] YYYY',
				'datetime_format'       => 'D [de] MMMM [de] YYYY HH:mm',
				'active'                => '1',
				'default'               => '0',
				'parent_id'             => null,
				'lft'                   => '6',
				'rgt'                   => '7',
				'depth'                 => '1',
				'deleted_at'            => null,
				'created_at'            => now()->format('Y-m-d H:i:s'),
				'updated_at'            => now()->format('Y-m-d H:i:s'),
			],
			[
				'code'                  => 'ar',
				'locale'                => $this->getUtf8Locale('ar_SA'),
				'name'                  => 'Arabic',
				'native'                => 'العربية',
				'flag'                  => 'flag-icon-sa',
				'script'                => 'Arab',
				'direction'             => 'rtl',
				'russian_pluralization' => '0',
				'date_format'           => 'DD/MMMM/YYYY',
				'datetime_format'       => 'DD/MMMM/YYYY HH:mm',
				'active'                => '1',
				'default'               => '0',
				'parent_id'             => null,
				'lft'                   => '8',
				'rgt'                   => '9',
				'depth'                 => '1',
				'deleted_at'            => null,
				'created_at'            => now()->format('Y-m-d H:i:s'),
				'updated_at'            => now()->format('Y-m-d H:i:s'),
			],
		];
		
		$tableName = (new Language())->getTable();
		foreach ($entries as $entry) {
			DB::table($tableName)->insert($entry);
		}
	}
	
	/**
	 * @param string $locale
	 * @return string
	 */
	private function getUtf8Locale(string $locale): string
	{
		// Limit the use of this method only for locales which often produce malfunctions
		// when they don't have their UTF-8 format. e.g. the Turkish language (tr_TR).
		$localesToFix = ['tr_TR'];
		if (!in_array($locale, $localesToFix)) {
			return $locale;
		}
		
		$localesList = getLocales('installed');
		
		// Return the given locale, if installed locales list cannot be retrieved from the server
		if (empty($localesList)) {
			return $locale;
		}
		
		// Return given locale, if the database charset is not utf-8
		$dbCharset = config('database.connections.' . config('database.default') . '.charset');
		if (!str_starts_with($dbCharset, 'utf8')) {
			return $locale;
		}
		
		$utf8LocaleFound = false;
		
		$codesetList = ['UTF-8', 'utf8'];
		foreach ($codesetList as $codeset) {
			$tmpLocale = $locale . '.' . $codeset;
			if (in_array($tmpLocale, $localesList, true)) {
				$locale = $tmpLocale;
				$utf8LocaleFound = true;
				break;
			}
		}
		
		if (!$utf8LocaleFound) {
			$codesetList = ['utf-8', 'UTF8'];
			foreach ($codesetList as $codeset) {
				$tmpLocale = $locale . '.' . $codeset;
				if (in_array($tmpLocale, $localesList, true)) {
					$locale = $tmpLocale;
					break;
				}
			}
		}
		
		return $locale;
	}
}
