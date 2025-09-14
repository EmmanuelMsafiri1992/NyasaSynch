<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PackageSeeder extends Seeder
{
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run()
	{
		// Packages' "active" column value
		$appUrl = config('app.url'); // env('APP_URL');
		$isDemoDomain = (isDemoDomain($appUrl) || isDevEnv($appUrl));
		$activeValue = $isDemoDomain ? '1' : '0';
		
		$entries = [
			// promotion
			[
				'type'                  => 'promotion',
				'name'                  => [
					'en' => 'Regular List',
					'fr' => 'Gratuit',
					'es' => 'Lista regular',
					'ar' => 'قائمة منتظمة',
				],
				'short_name'            => [
					'en' => 'Free',
					'fr' => 'Standard',
					'es' => 'Estándar',
					'ar' => 'اساسي',
				],
				'ribbon'                => null,
				'has_badge'             => '0',
				'price'                 => '0.00',
				'currency_code'         => 'USD',
				'promotion_time'        => null,
				'expiration_time'       => null,
				'description'           => null,
				'facebook_ads_duration' => '0',
				'google_ads_duration'   => '0',
				'twitter_ads_duration'  => '0',
				'linkedin_ads_duration' => '0',
				'recommended'           => '0',
				'parent_id'             => null,
				'lft'                   => '2',
				'rgt'                   => '3',
				'depth'                 => '0',
				'active'                => $activeValue,
			],
			[
				'type'                  => 'promotion',
				'name'                  => [
					'en' => 'Premium Listing',
					'fr' => 'Annonce Premium',
					'es' => 'Anuncio premium',
					'ar' => 'إعلان مميز',
				],
				'short_name'            => [
					'en' => 'Premium',
					'fr' => 'Premium',
					'es' => 'Prima',
					'ar' => 'الممتازة',
				],
				'ribbon'                => null,
				'has_badge'             => '0',
				'price'                 => '99.00',
				'currency_code'         => 'USD',
				'promotion_time'        => '7',
				'expiration_time'       => '60',
				'description'           => [
					'en' => "Featured on the Homepage\nFeatured in the Category",
					'fr' => "En vedette à l'accueil\nEn vedette dans la catégorie",
					'es' => "Destacado en la página de inicio\nDestacado en la categoría",
					'ar' => "ظهرت في الاستقبال\nظهرت في الفئة",
				],
				'facebook_ads_duration' => '0',
				'google_ads_duration'   => '0',
				'twitter_ads_duration'  => '0',
				'linkedin_ads_duration' => '0',
				'recommended'           => '1',
				'parent_id'             => null,
				'lft'                   => '4',
				'rgt'                   => '5',
				'depth'                 => '0',
				'active'                => $activeValue,
			],
			[
				'type'                  => 'promotion',
				'name'                  => [
					'en' => 'Premium Listing (+)',
					'fr' => 'Annonce Premium (+)',
					'es' => 'Anuncio premium (+)',
					'ar' => 'إعلان مميز (+)',
				],
				'short_name'            => [
					'en' => 'Premium+',
					'fr' => 'Premium+',
					'es' => 'Prima+',
					'ar' => 'الممتازة+',
				],
				'ribbon'                => null,
				'has_badge'             => '0',
				'price'                 => '129.00',
				'currency_code'         => 'USD',
				'promotion_time'        => '30',
				'expiration_time'       => '120',
				'description'           => [
					'en' => "Featured on the Homepage\nFeatured in the Category",
					'fr' => "En vedette à l'accueil\nEn vedette dans la catégorie",
					'es' => "Destacado en la página de inicio\nDestacado en la categoría",
					'ar' => "ظهرت في الاستقبال\nظهرت في الفئة",
				],
				'facebook_ads_duration' => '0',
				'google_ads_duration'   => '0',
				'twitter_ads_duration'  => '0',
				'linkedin_ads_duration' => '0',
				'recommended'           => '0',
				'parent_id'             => null,
				'lft'                   => '6',
				'rgt'                   => '7',
				'depth'                 => '0',
				'active'                => $activeValue,
			],
			
			// subscription
			[
				'type'            => 'subscription',
				'name'            => [
					'en' => 'Basic',
					'fr' => 'Basique',
					'es' => 'Básico',
					'ar' => 'أساسي',
				],
				'short_name'      => [
					'en' => 'Basic',
					'fr' => 'Basique',
					'es' => 'Básico',
					'ar' => 'أساسي',
				],
				'price'           => '0.00',
				'currency_code'   => 'USD',
				'interval'        => 'month', // week, month, year or null
				'listings_limit'  => null,
				'expiration_time' => null,
				'description'     => null,
				'recommended'     => '0',
				'parent_id'       => null,
				'lft'             => '8',
				'rgt'             => '9',
				'depth'           => '0',
				'active'          => $activeValue,
			],
			[
				'type'            => 'subscription',
				'name'            => [
					'en' => 'Premium',
					'fr' => 'Premium',
					'es' => 'Prima',
					'ar' => 'الممتازة',
				],
				'short_name'      => [
					'en' => 'Premium',
					'fr' => 'Premium',
					'es' => 'Prima',
					'ar' => 'الممتازة',
				],
				'price'           => '9.00',
				'currency_code'   => 'USD',
				'interval'        => 'month', // week, month, year or null
				'listings_limit'  => '100',
				'expiration_time' => '90',
				'description'     => null,
				'recommended'     => '1',
				'parent_id'       => null,
				'lft'             => '10',
				'rgt'             => '11',
				'depth'           => '0',
				'active'          => $activeValue,
			],
		];
		
		$tableName = (new Package())->getTable();
		foreach ($entries as $entry) {
			$entry = arrayTranslationsToJson($entry);
			$entryId = DB::table($tableName)->insertGetId($entry);
		}
	}
}
