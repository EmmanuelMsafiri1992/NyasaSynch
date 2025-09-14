<?php

use App\Exceptions\Custom\CustomException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;

// ===| FILES |===
try {
	
	File::delete(app_path('Models/Post/LatestOrPremium.php'));
	File::delete(app_path('Models/HomeSection/GetLatestPosts.php'));
	File::delete(app_path('Models/HomeSection/GetSponsoredPosts.php'));
	File::delete(app_path('Models/HomeSection/GetFeaturedPostsCompanies.php'));
	File::delete(resource_path('views/home/inc/featured.blade.php'));
	if (file_exists(storage_path('framework/plugins/domainmapping'))) {
		File::delete(base_path('extras/plugins/domainmapping/app/Http/Controllers/Admin/DomainController.php'));
		File::delete(base_path('extras/plugins/domainmapping/app/Http/Controllers/Admin/DomainHomeSectionController.php'));
		File::delete(base_path('extras/plugins/domainmapping/app/Http/Controllers/Admin/DomainMetaTagController.php'));
		File::delete(base_path('extras/plugins/domainmapping/app/Http/Controllers/Admin/DomainSettingController.php'));
		
		File::delete(base_path('extras/plugins/domainmapping/app/Models/HomeSection/GetLatestPosts.php'));
		File::delete(base_path('extras/plugins/domainmapping/app/Models/HomeSection/GetSponsoredPosts.php'));
		File::delete(base_path('extras/plugins/domainmapping/app/Models/HomeSection/GetFeaturedPostsCompanies.php'));
	}
	
	File::deleteDirectory(app_path('Http/Controllers/Admin/'));
	File::deleteDirectory(app_path('Http/Controllers/Web/Account/'));
	File::deleteDirectory(app_path('Http/Controllers/Web/Ajax/'));
	File::deleteDirectory(app_path('Http/Controllers/Web/Auth/'));
	File::deleteDirectory(app_path('Http/Controllers/Web/Locale/'));
	File::deleteDirectory(app_path('Http/Controllers/Web/Post/'));
	File::deleteDirectory(app_path('Http/Controllers/Web/Search/'));
	File::deleteDirectory(app_path('Http/Controllers/Web/Traits/'));
	File::delete(app_path('Http/Controllers/Web/CountriesController.php'));
	File::delete(app_path('Http/Controllers/Web/FileController.php'));
	File::delete(app_path('Http/Controllers/Web/FrontController.php'));
	File::delete(app_path('Http/Controllers/Web/HomeController.php'));
	File::delete(app_path('Http/Controllers/Web/PageController.php'));
	File::delete(app_path('Http/Controllers/Web/SitemapController.php'));
	File::delete(app_path('Http/Controllers/Web/SitemapsController.php'));
	File::delete(app_path('Http/Middleware/InstallationChecker.php'));
	
} catch (\Throwable $e) {
}

// ===| DATABASE |===
try {
	
	// home_sections
	$premiumSection = \App\Models\HomeSection::where('method', 'getSponsoredPosts')->first();
	if (!empty($premiumSection)) {
		$premiumSection->method = 'getPremiumListings';
		$premiumSection->name = 'Premium Listings';
		$premiumSection->view = 'home.inc.premium';
		$premiumSection->save();
	}
	
	$latestSection = \App\Models\HomeSection::where('method', 'getLatestPosts')->first();
	if (!empty($latestSection)) {
		$latestSection->method = 'getLatestListings';
		$latestSection->name = 'Latest Listings';
		$latestSection->save();
	}
	
	$companiesSection = \App\Models\HomeSection::where('method', 'getFeaturedPostsCompanies')->first();
	if (!empty($companiesSection)) {
		$companiesSection->method = 'getCompanies';
		$companiesSection->save();
	}
	
	// personal_access_tokens
	if (!Schema::hasColumn('personal_access_tokens', 'expires_at')) {
		Schema::table('personal_access_tokens', function (Blueprint $table) {
			$table->timestamp('expires_at')->nullable()->after('last_used_at');
		});
	}
	
	// posts
	if (!Schema::hasColumn('posts', 'currency_code')) {
		Schema::table('posts', function (Blueprint $table) {
			$table->string('currency_code', 3)->nullable()->after('salary_type_id');
		});
	}
	if (Schema::hasColumn('posts', 'currency_code')) {
		DB::table('posts')->lazyById()->each(function ($post) {
			if (empty($post->currency_code)) {
				$country = \App\Models\Country::where('code', $post->country_code)->first();
				if (!empty($country)) {
					DB::table('posts')->where('id', $post->id)->update(['currency_code' => $country->currency_code]);
				}
			}
		});
	}
	
} catch (\Throwable $e) {
	
	$message = $e->getMessage() . "\n" . 'in ' . str_replace(base_path(), '', __FILE__);
	throw new CustomException($message);
	
}
