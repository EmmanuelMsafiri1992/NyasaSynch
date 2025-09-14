<?php

use App\Exceptions\Custom\CustomException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;

// ===| FILES |===
try {
	
	File::delete(app_path('Http/Controllers/Admin/SubCategoryController.php'));
	File::delete(app_path('Http/Controllers/Post/CreateOrEdit/Traits/PaymentTrait.php'));
	File::delete(resource_path('views/post/createOrEdit/inc/form-plugins.blade.php'));
	File::delete(resource_path('views/search/serp.blade.php'));
	File::delete(resource_path('views/search/inc/fields.blade.php'));
	
} catch (\Throwable $e) {
}

// ===| DATABASE |===
try {
	
	// settings
	DB::table('settings')->where('key', '=', 'single')->update([
		'name'        => 'Ads (Form & Single Page)',
		'description' => 'Ads (Form & Single Page) Options',
	]);
	
	// payments
	if (!Schema::hasColumn('payments', 'amount')) {
		Schema::table('payments', function (Blueprint $table) {
			$table->decimal('amount', 10, 2)->default(0.00)->after('transaction_id');
		});
	}
	if (Schema::hasColumn('payments', 'amount')) {
		$payments = \App\Models\Payment::with(['package']);
		if ($payments->count() > 0) {
			foreach ($payments->cursor() as $payment) {
				if (isset($payment->package) && !empty($payment->package)) {
					$payment->amount = $payment->package->price;
				} else {
					$payment->amount = 0;
				}
				$payment->save();
			}
		}
	}
	
	// packages
	Schema::table('packages', function (Blueprint $table) {
		$table->text('description')->nullable()->change();
	});
	if (!Schema::hasColumn('packages', 'promotion_time')) {
		Schema::table('packages', function (Blueprint $table) {
			$table->integer('promotion_time')->nullable()->default(30)->comment('In days')->after('currency_code');
		});
	}
	if (!Schema::hasColumn('packages', 'pictures_limit')) {
		Schema::table('packages', function (Blueprint $table) {
			$table->integer('pictures_limit')->nullable()->default(0)->after('duration');
		});
	}
	if (!Schema::hasColumn('packages', 'facebook_ads')) {
		Schema::table('packages', function (Blueprint $table) {
			$table->boolean('facebook_ads')->nullable()->default(false)->after('description');
		});
	}
	if (!Schema::hasColumn('packages', 'google_ads')) {
		Schema::table('packages', function (Blueprint $table) {
			$table->boolean('google_ads')->nullable()->default(false)->after('facebook_ads');
		});
	}
	if (!Schema::hasColumn('packages', 'twitter_ads')) {
		Schema::table('packages', function (Blueprint $table) {
			$table->boolean('twitter_ads')->nullable()->default(false)->after('google_ads');
		});
	}
	if (!Schema::hasColumn('packages', 'recommended')) {
		Schema::table('packages', function (Blueprint $table) {
			$table->boolean('recommended')->nullable()->default(false)->after('twitter_ads');
		});
	}
	
	// meta_tags
	if (
		Schema::hasColumn('languages', 'abbr')
		&& Schema::hasColumn('meta_tags', 'translation_lang')
		&& Schema::hasColumn('meta_tags', 'translation_of')
	) {
		$languages = \App\Models\Language::query()->get();
		if ($languages->count() > 0) {
			DB::table('meta_tags')->where('page', 'pricing')->delete();
			$translationOf = null;
			foreach ($languages as $lang) {
				$metaTag = [
					'page'        => 'pricing',
					'title'       => 'Pricing - {app.name}',
					'description' => 'Pricing - {app.name}',
					'keywords'    => '{app.name}, {country.name}, pricing, free ads, classified, ads, script, app, premium ads',
					'active'      => 1,
				];
				if (Schema::hasColumn('meta_tags', 'translation_lang') && Schema::hasColumn('meta_tags', 'translation_lang')) {
					$metaTag = [
						'translation_lang' => $lang->abbr,
						'translation_of'   => 0,
						'page'             => 'pricing',
						'title'            => 'Pricing - {app.name}',
						'description'      => 'Pricing - {app.name}',
						'keywords'         => '{app.name}, {country.name}, pricing, free ads, classified, ads, script, app, premium ads',
						'active'           => 1,
					];
				}
				$metaTagId = DB::table('meta_tags')->insertGetId($metaTag);
				if ($lang->abbr == config('appLang.abbr')) {
					$translationOf = $metaTagId;
				}
			}
			if (!empty($translationOf)) {
				if (Schema::hasColumn('meta_tags', 'translation_lang') && Schema::hasColumn('meta_tags', 'translation_lang')) {
					$affected = DB::table('meta_tags')
						->where('page', 'pricing')
						->update(['translation_of' => $translationOf]);
				}
			}
		}
	}
	
	// categories
	$params = [
		'adjacentTable' => 'categories',
		'nestedTable'   => 'categories',
	];
	$transformer = new \App\Helpers\Categories\AdjacentToNested($params);
	$transformer->getAndSetAdjacentItemsIds();
	$transformer->convertChildrenRecursively(0);
	$transformer->setNodesDepth();
	
	// Create the Nested Set indexes ('lft', 'rgt' & 'depth')
	$transformer->createNestedSetIndexes();
	
} catch (\Throwable $e) {
	
	$message = $e->getMessage() . "\n" . 'in ' . str_replace(base_path(), '', __FILE__);
	throw new CustomException($message);
	
}
