<?php

use App\Exceptions\Custom\CustomException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

// ===| FILES |===
try {
	File::deleteDirectory(app_path('Larapen/'));
	File::delete(base_path('gulpfile.js'));
	// File::delete(base_path('package.json'));
	File::delete(base_path('phpspec.yml'));
	File::delete(base_path('phpunit.xml'));
	
} catch (\Throwable $e) {
}

// ===| DATABASE |===
try {
	
	// pages
	if (Schema::hasColumn('pages', 'type')) {
		Schema::table('pages', function (Blueprint $table) {
			$table->enum('type', ['standard', 'terms', 'privacy', 'tips'])->change();
		});
	}
	if (
		!Schema::hasColumn('pages', 'name')
		&& Schema::hasColumn('pages', 'type')
	) {
		Schema::table('pages', function (Blueprint $table) {
			$table->string('name', 200)->nullable()->after('type');
		});
	}
	if (!Schema::hasColumn('pages', 'excluded_from_footer') && Schema::hasColumn('pages', 'title_color')) {
		Schema::table('pages', function (Blueprint $table) {
			$table->boolean('excluded_from_footer')->unsigned()->default(0)->after('title_color');
		});
	}
	
	// ads
	if (Schema::hasTable('ads')) {
		if (!Schema::hasColumn('ads', 'featured') && Schema::hasColumn('ads', 'reviewed')) {
			Schema::table('ads', function (Blueprint $table) {
				$table->boolean('featured')->unsigned()->nullable()->default(0)->after('reviewed');
				$table->index('featured');
			});
		}
	}
	
	// settings
	if (Schema::hasColumn('settings', 'value')) {
		Schema::table('settings', function (Blueprint $table) {
			$table->text('value')->nullable()->change();
		});
	}
	
	DB::table('settings')->where('key', 'activation_serp_left_sidebar')->delete();
	DB::table('settings')->where('key', 'like', 'paypal%')->delete();
	
	$allData = [
		[
			'key'         => 'upload_image_types',
			'name'        => 'Upload Image Types',
			'value'       => 'jpg,jpeg,gif,png',
			'description' => 'Upload image types (ex: jpg,jpeg,gif,png,...)',
			'field'       => '{"name":"value","label":"Value","type":"text"}',
			'parent_id'   => 0,
			'lft'         => 20,
			'rgt'         => 21,
			'depth'       => 1,
			'active'      => 1,
			'created_at'  => null,
			'updated_at'  => '2017-02-21 15:02:43',
		],
		[
			'key'         => 'upload_file_types',
			'name'        => 'Upload File Types',
			'value'       => 'pdf,doc,docx,word,rtf,rtx,ppt,pptx,odt,odp,wps,jpeg,jpg,bmp,png',
			'description' => 'Upload file types (ex: pdf,doc,docx,odt,...)',
			'field'       => '{"name":"value","label":"Value","type":"text"}',
			'parent_id'   => 0,
			'lft'         => 20,
			'rgt'         => 21,
			'depth'       => 1,
			'active'      => 1,
			'created_at'  => null,
			'updated_at'  => '2017-02-21 15:03:06',
		],
		[
			'key'         => 'app_favicon',
			'name'        => 'Favicon',
			'value'       => null,
			'description' => 'Favicon (extension: png,jpg)',
			'field'       => '{"name":"value","label":"Favicon","type":"image","upload":"true","disk":"uploads","default":"app/default/ico/favicon.png"}',
			'parent_id'   => 0,
			'lft'         => 4,
			'rgt'         => 4,
			'depth'       => 1,
			'active'      => 1,
			'created_at'  => null,
			'updated_at'  => '2017-02-24 09:15:38',
		],
		[
			'key'         => 'unactivated_ads_expiration',
			'name'        => 'Unactivated Ads Expiration',
			'value'       => '30',
			'description' => 'In days (Delete the unactivated ads after this expiration) - You need to add "/usr/bin/php -q /path/to/your/website/artisan ads:clean" in your Cron Job tab',
			'field'       => '{"name":"value","label":"Value","type":"text"}',
			'parent_id'   => 0,
			'lft'         => 25,
			'rgt'         => 25,
			'depth'       => 1,
			'active'      => 1,
			'created_at'  => null,
			'updated_at'  => '2017-03-14 19:31:10',
		],
		[
			'key'         => 'activated_ads_expiration',
			'name'        => 'Activated Ads Expiration',
			'value'       => '150',
			'description' => 'In days (Archive the activated ads after this expiration) - You need to add "/usr/bin/php -q /path/to/your/website/artisan ads:clean" in your Cron Job tab',
			'field'       => '{"name":"value","label":"Value","type":"text"}',
			'parent_id'   => 0,
			'lft'         => 25,
			'rgt'         => 25,
			'depth'       => 1,
			'active'      => 1,
			'created_at'  => null,
			'updated_at'  => '2017-03-14 19:31:10',
		],
		[
			'key'         => 'archived_ads_expiration',
			'name'        => 'Archived Ads Expiration',
			'value'       => '7',
			'description' => 'In days (Delete the archived ads after this expiration) - You need to add "/usr/bin/php -q /path/to/your/website/artisan ads:clean" in your Cron Job tab',
			'field'       => '{"name":"value","label":"Value","type":"text"}',
			'parent_id'   => 0,
			'lft'         => 25,
			'rgt'         => 25,
			'depth'       => 1,
			'active'      => 1,
			'created_at'  => null,
			'updated_at'  => '2017-03-14 19:31:10',
		],
		[
			'key'         => 'app_email_sender',
			'name'        => 'Email Sender',
			'value'       => null,
			'description' => 'Transactional Email Sender. Example: noreply@yoursite.com',
			'field'       => '{"name":"value","label":"Value","type":"email"}',
			'parent_id'   => 0,
			'lft'         => 9,
			'rgt'         => 10,
			'depth'       => 1,
			'active'      => 1,
			'created_at'  => null,
			'updated_at'  => '2017-03-22 09:27:49',
		],
	];
	foreach ($allData as $item) {
		$key = $item['key'] ?? '';
		
		$setting = \App\Models\Setting::where('key', $key)->first();
		if (empty($setting)) {
			DB::table('settings')->insert($item);
		}
	}
	
	$field = '{"name":"value","label":"Logo","type":"image","upload":"true","disk":"uploads","default":"app/default/logo.png"}';
	DB::table('settings')->where('key', 'app_logo')->update(['field' => $field]);
	
	$field = '{"name":"value","label":"Value","type":"textarea","hint":"Please <strong>do not</strong> include the &lt;style&gt; tags."}';
	DB::table('settings')->where('key', 'custom_css')->update(['field' => $field]);
	
	$hint = 'Paste your Google Analytics (or other) tracking code here. This will be added into the footer. <br>Please <strong>do not</strong> include the &lt;script&gt; tags.';
	$field = '{"name":"value","label":"Value","type":"textarea","hint":"' . $hint . '"}';
	DB::table('settings')
		->where('key', 'seo_google_analytics')
		->update([
			'key'         => 'tracking_code',
			'name'        => 'Tracking Code',
			'description' => 'Tracking Code (ex: Google Analytics Code)',
			'field'       => $field,
		]);
	
	// UPDATE `settings` s, (SELECT `key`, `value` FROM `settings` WHERE `key`='app_email') ss SET s.value=ss.value WHERE s.key='app_email_sender';
	$prefix = DB::getTablePrefix();
	$rawTable = $prefix . 'settings';
	DB::table('settings as s')
		->join(DB::raw('(SELECT `key`, `value` FROM `' . $rawTable . '` WHERE `key` = "app_email") as ss'), 's.key', '=', DB::raw('ss.key'))
		->where('s.key', 'app_email_sender')
		->update(['s.value' => DB::raw('ss.value')]);
	
	DB::table('settings')->update(['parent_id' => 0]);
	
	// payment_methods
	Schema::dropIfExists('payment_methods');
	
	if (!Schema::hasTable('payment_methods')) {
		Schema::create('payment_methods', function (Blueprint $table) {
			$table->id();
			$table->string('name', 100)->nullable();
			$table->string('display_name', 100)->nullable();
			$table->string('description', 255)->default('');
			$table->tinyInteger('has_ccbox')->unsigned()->default(0);
			$table->unsignedBigInteger('lft')->nullable();
			$table->unsignedBigInteger('rgt')->nullable();
			$table->unsignedBigInteger('depth')->nullable();
			$table->tinyInteger('active')->default(0);
			$table->timestamps();
		});
	}
	
	$paymentMethod = \App\Models\PaymentMethod::where('name', 'paypal')->first();
	if (empty($paymentMethod)) {
		DB::table('payment_methods')->insert([
			'id'           => 1,
			'name'         => 'paypal',
			'display_name' => 'Paypal',
			'description'  => 'Payment with Paypal',
			'has_ccbox'    => 0,
			'lft'          => 0,
			'rgt'          => 0,
			'depth'        => 1,
			'active'       => 1,
		]);
	}
	
	// ads
	if (Schema::hasTable('ads')) {
		DB::table('ads')->join('payments as p', 'p.ad_id', '=', 'ads.id')->update(['ads.featured' => 1]);
	}
	
} catch (\Throwable $e) {
	
	$message = $e->getMessage() . "\n" . 'in ' . str_replace(base_path(), '', __FILE__);
	throw new CustomException($message);
	
}
