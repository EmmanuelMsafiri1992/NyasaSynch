<?php

use App\Exceptions\Custom\CustomException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// ===| DATABASE |===
try {
	
	// saved_search
	DB::table('saved_search')->truncate();
	
	if (!Schema::hasColumn('saved_search', 'country_code') && Schema::hasColumn('saved_search', 'id')) {
		Schema::table('saved_search', function (Blueprint $table) {
			$table->string('country_code', 2)->nullable()->after('id');
			$table->index('country_code');
		});
	}
	
	// users
	if (Schema::hasColumn('users', 'comments_enabled') && !Schema::hasColumn('users', 'disable_comments')) {
		Schema::table('users', function (Blueprint $table) {
			$table->renameColumn('comments_enabled', 'disable_comments');
		});
	}
	if (Schema::hasColumn('users', 'disable_comments')) {
		Schema::table('users', function (Blueprint $table) {
			$table->boolean('disable_comments')->unsigned()->nullable()->default(0)->change();
		});
	}
	
} catch (\Throwable $e) {
	
	$message = $e->getMessage() . "\n" . 'in ' . str_replace(base_path(), '', __FILE__);
	throw new CustomException($message);
	
}
