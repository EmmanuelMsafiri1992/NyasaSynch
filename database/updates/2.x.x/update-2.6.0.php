<?php

use App\Exceptions\Custom\CustomException;
use App\Helpers\DBTool;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// ===| DATABASE |===
try {
	
	// companies
	if (!Schema::hasTable('companies')) {
		Schema::create('companies', function (Blueprint $table) {
			$table->bigIncrements('id')->unsigned();
			$table->bigInteger('user_id')->unsigned();
			$table->string('name', 255);
			$table->string('logo', 255)->nullable();
			$table->text('description')->nullable();
			$table->string('country_code', 2);
			$table->bigInteger('city_id')->unsigned()->default(0);
			$table->string('address', 255)->nullable();
			$table->string('phone', 60)->nullable();
			$table->string('fax', 60)->nullable();
			$table->string('email', 100)->nullable();
			$table->string('website', 255)->nullable();
			$table->string('facebook', 200)->nullable();
			$table->string('twitter', 200)->nullable();
			$table->string('linkedin', 200)->nullable();
			$table->string('googleplus', 200)->nullable();
			$table->string('pinterest', 200)->nullable();
			$table->timestamps();
			
			// Indexes
			$table->index('user_id');
			$table->index('country_code');
			$table->index('city_id');
		});
	}
	
	// payment_methods
	if (Schema::hasColumn('payment_methods', 'description')) {
		Schema::table('payment_methods', function (Blueprint $table) {
			$table->text('description')->nullable()->change();
		});
	}
	
	// posts
	if (!Schema::hasColumn('posts', 'company_id') && Schema::hasColumn('posts', 'user_id')) {
		Schema::table('posts', function (Blueprint $table) {
			$table->bigInteger('company_id')->unsigned()->nullable()->default(0)->after('user_id');
			$table->index('company_id');
		});
	}
	if (!Schema::hasColumn('posts', 'application_url') && Schema::hasColumn('posts', 'start_date')) {
		Schema::table('posts', function (Blueprint $table) {
			$table->string('application_url', 255)->nullable()->after('start_date');
		});
	}
	if (!Schema::hasColumn('posts', 'tags') && Schema::hasColumn('posts', 'description')) {
		Schema::table('posts', function (Blueprint $table) {
			$table->string('tags', 255)->nullable()->after('description');
			$table->index('tags');
		});
	}
	if (Schema::hasColumn('posts', 'company_website')) {
		Schema::table('posts', function (Blueprint $table) {
			$table->dropColumn('company_website');
		});
	}
	if (Schema::hasColumn('posts', 'company_description')) {
		Schema::table('posts', function (Blueprint $table) {
			$table->text('company_description')->nullable()->change();
		});
	}
	
	// resumes
	if (!Schema::hasColumn('resumes', 'name') && Schema::hasColumn('resumes', 'user_id')) {
		Schema::table('resumes', function (Blueprint $table) {
			$table->string('name', 200)->nullable()->after('user_id');
		});
	}
	if (Schema::hasColumn('resumes', 'filename')) {
		Schema::table('resumes', function (Blueprint $table) {
			$table->string('filename', 255)->nullable()->change();
		});
	}
	
	// settings
	DB::table('settings')->where('key', 'activation_guests_can_post')->update(['key' => 'guest_can_submit_listings']);
	
	$hint = 'Paste your Google Analytics (or other) tracking code here. This will be added into the footer.';
	$field = '{"name":"value","label":"Value","type":"textarea","hint":"' . $hint . '"}';
	DB::table('settings')->where('key', 'tracking_code')->update(['field' => $field]);
	
	$setting = \App\Models\Setting::where('key', 'guests_can_apply_jobs')->first();
	if (empty($setting)) {
		DB::table('settings')->insert([
			'key'         => 'guests_can_apply_jobs',
			'name'        => 'Guests can apply Jobs',
			'value'       => '1',
			'description' => 'Guests can apply Jobs',
			'field'       => '{"name":"value","label":"Activation","type":"checkbox"}',
			'parent_id'   => 0,
			'lft'         => 62,
			'rgt'         => 63,
			'depth'       => 1,
			'active'      => 1,
			'created_at'  => null,
			'updated_at'  => '2017-09-15 08:14:08',
		]);
	}
	
} catch (\Throwable $e) {
	
	$message = $e->getMessage() . "\n" . 'in ' . str_replace(base_path(), '', __FILE__);
	throw new CustomException($message);
	
}
