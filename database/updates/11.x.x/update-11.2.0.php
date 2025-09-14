<?php

use App\Exceptions\Custom\CustomException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;

// ===| FILES |===
try {
	
	File::delete(app_path('Http/Controllers/Api/Post/CreateOrEdit/Traits/CategoriesTrait.php'));
	File::delete(app_path('Http/Controllers/Web/Ajax/LocationController.php'));
	File::delete(public_path('assets/js/app/d.select.category.js'));
	File::delete(public_path('assets/js/app/load.cities.js'));
	File::delete(resource_path('views/post/createOrEdit/inc/category/parent.blade.php'));
	
} catch (\Throwable $e) {
}

// ===| DATABASE |===
try {
	
	// countries
	if (Schema::hasColumn('countries', 'admin_field_active')) {
		Schema::table('countries', function (Blueprint $table) {
			$table->dropColumn('admin_field_active');
		});
	}
	
} catch (\Throwable $e) {
	
	$message = $e->getMessage() . "\n" . 'in ' . str_replace(base_path(), '', __FILE__);
	throw new CustomException($message);
	
}
